<?php
class KNNAlgorithm {
    private $conn;
    private $training_data;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->loadTrainingData();
    }
    
    private function loadTrainingData() {
        $sql = "SELECT * FROM knn_training_data ORDER BY no_data";
        $result = $this->conn->query($sql);
        $this->training_data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->training_data[] = $row;
            }
        }
    }
    
    public function predict($input_data, $k = 3) {
        if (empty($this->training_data)) {
            return null;
        }
        
        // Normalize input data
        $normalized_input = $this->normalizeInput($input_data);
        
        // Calculate distances to all training data
        $distances = [];
        foreach ($this->training_data as $index => $training_row) {
            $normalized_training = $this->normalizeTrainingRow($training_row);
            $distance = $this->calculateEuclideanDistance($normalized_input, $normalized_training);
            
            $distances[] = [
                'index' => $index,
                'distance' => $distance,
                'data' => $training_row,
                'normalized' => $normalized_training
            ];
        }
        
        // Sort by distance (ascending)
        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        
        // Get K nearest neighbors
        $k_neighbors = array_slice($distances, 0, $k);
        
        // Count votes for each class
        $class_votes = [];
        foreach ($k_neighbors as $neighbor) {
            $class = $neighbor['data']['kelas'];
            if (!isset($class_votes[$class])) {
                $class_votes[$class] = 0;
            }
            $class_votes[$class]++;
        }
        
        // Get predicted class (majority vote)
        arsort($class_votes);
        $predicted_class = array_key_first($class_votes);
        
        // Calculate confidence (percentage of majority class)
        $total_votes = array_sum($class_votes);
        $confidence = $class_votes[$predicted_class] / $total_votes;
        
        return [
            'predicted_class' => $predicted_class,
            'confidence' => $confidence,
            'class_votes' => $class_votes,
            'k_neighbors' => $k_neighbors,
            'all_distances' => $distances,
            'input_normalized' => $normalized_input
        ];
    }
    
    private function normalizeInput($input) {
        // Convert categorical to numerical
        $normalized = [];
        
        // Numerical features (min-max normalization)
        $normalized['harga'] = $this->normalizeFeature($input['harga'], 'harga');
        $normalized['berat'] = $this->normalizeFeature($input['berat'], 'berat');
        
        // Categorical features (binary encoding)
        $normalized['standar'] = $this->encodeStandar($input['standar']);
        $normalized['kaca'] = ($input['kaca'] === 'Ya') ? 1 : 0;
        $normalized['double_visor'] = ($input['double_visor'] === 'Ya') ? 1 : 0;
        $normalized['ventilasi_udara'] = ($input['ventilasi_udara'] === 'Ya') ? 1 : 0;
        $normalized['wire_lock'] = ($input['wire_lock'] === 'Ya') ? 1 : 0;
        
        return $normalized;
    }
    
    private function normalizeTrainingRow($row) {
        $normalized = [];
        
        // Numerical features
        $normalized['harga'] = $this->normalizeFeature($row['harga'], 'harga');
        $normalized['berat'] = $this->normalizeFeature($row['berat'], 'berat');
        
        // Categorical features
        $normalized['standar'] = $this->encodeStandar($row['standar']);
        $normalized['kaca'] = ($row['kaca'] === 'Ya') ? 1 : 0;
        $normalized['double_visor'] = ($row['double_visor'] === 'Ya') ? 1 : 0;
        $normalized['ventilasi_udara'] = ($row['ventilasi_udara'] === 'Ya') ? 1 : 0;
        $normalized['wire_lock'] = ($row['wire_lock'] === 'Ya') ? 1 : 0;
        
        return $normalized;
    }
    
    private function normalizeFeature($value, $feature) {
        // Simple ratio normalization: Value / Max (like Excel method)
        $values = array_column($this->training_data, $feature);
        $max = max($values);
        
        if ($max == 0) return 0; // Avoid division by zero
        
        return $value / $max;
    }
    
    private function encodeStandar($standar) {
        // Encode standar as numerical value based on safety level, then normalize (value/max)
        // Max standar level is 4, so we normalize by dividing by 4
        switch ($standar) {
            case 'SNI': return 1/4;                      // 0.25
            case 'SNI, DOT': return 2/4;                 // 0.5
            case 'SNI, DOT, ECE': return 3/4;            // 0.75
            case 'SNI, DOT, ECE, SNELL': return 4/4;     // 1.0
            default: return 0;
        }
    }
    
    private function calculateEuclideanDistance($point1, $point2) {
        $sum = 0;
        $features = ['harga', 'berat', 'standar', 'kaca', 'double_visor', 'ventilasi_udara', 'wire_lock'];
        
        foreach ($features as $feature) {
            $diff = $point1[$feature] - $point2[$feature];
            $sum += $diff * $diff;
        }
        
        return sqrt($sum);
    }
    
    public function getTrainingDataStats() {
        if (empty($this->training_data)) {
            return null;
        }
        
        $stats = [
            'total_data' => count($this->training_data),
            'features' => [
                'harga' => [
                    'min' => min(array_column($this->training_data, 'harga')),
                    'max' => max(array_column($this->training_data, 'harga')),
                    'avg' => array_sum(array_column($this->training_data, 'harga')) / count($this->training_data)
                ],
                'berat' => [
                    'min' => min(array_column($this->training_data, 'berat')),
                    'max' => max(array_column($this->training_data, 'berat')),
                    'avg' => array_sum(array_column($this->training_data, 'berat')) / count($this->training_data)
                ]
            ],
            'class_distribution' => []
        ];
        
        // Count class distribution
        foreach ($this->training_data as $row) {
            $class = $row['kelas'];
            if (!isset($stats['class_distribution'][$class])) {
                $stats['class_distribution'][$class] = 0;
            }
            $stats['class_distribution'][$class]++;
        }
        
        return $stats;
    }
}
?>