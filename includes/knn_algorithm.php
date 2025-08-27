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
            $diff = abs($point1[$feature] - $point2[$feature]);
            $sum += $diff;
        }
        
        return $sum;  // Manhattan distance (sum of absolute differences)
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
    
    // Cross Validation method
    public function crossValidation($k = 3, $folds = 5) {
        if (empty($this->training_data)) {
            return null;
        }
        
        $data_count = count($this->training_data);
        $fold_size = intval($data_count / $folds);
        $results = [];
        
        // Shuffle data for random distribution
        $shuffled_data = $this->training_data;
        shuffle($shuffled_data);
        
        for ($i = 0; $i < $folds; $i++) {
            $start = $i * $fold_size;
            $end = ($i == $folds - 1) ? $data_count : ($i + 1) * $fold_size;
            
            // Split data into training and testing sets
            $test_set = array_slice($shuffled_data, $start, $end - $start);
            $train_set = array_merge(
                array_slice($shuffled_data, 0, $start),
                array_slice($shuffled_data, $end)
            );
            
            // Temporarily replace training data
            $original_training = $this->training_data;
            $this->training_data = $train_set;
            
            // Test each item in test set
            $fold_predictions = [];
            foreach ($test_set as $test_item) {
                $input_data = [
                    'harga' => $test_item['harga'],
                    'standar' => $test_item['standar'],
                    'kaca' => $test_item['kaca'],
                    'double_visor' => $test_item['double_visor'],
                    'ventilasi_udara' => $test_item['ventilasi_udara'],
                    'berat' => $test_item['berat'],
                    'wire_lock' => $test_item['wire_lock']
                ];
                
                $prediction = $this->predict($input_data, $k);
                
                $fold_predictions[] = [
                    'actual' => $test_item['kelas'],
                    'predicted' => $prediction ? $prediction['predicted_class'] : null,
                    'confidence' => $prediction ? $prediction['confidence'] : 0,
                    'test_item' => $test_item
                ];
            }
            
            // Restore original training data
            $this->training_data = $original_training;
            
            $results[] = [
                'fold' => $i + 1,
                'predictions' => $fold_predictions,
                'accuracy' => $this->calculateAccuracy($fold_predictions)
            ];
        }
        
        // Calculate overall metrics
        $all_predictions = [];
        foreach ($results as $fold) {
            $all_predictions = array_merge($all_predictions, $fold['predictions']);
        }
        
        $overall_accuracy = $this->calculateAccuracy($all_predictions);
        $confusion_matrix = $this->calculateConfusionMatrix($all_predictions);
        
        return [
            'folds' => $results,
            'overall_accuracy' => $overall_accuracy,
            'confusion_matrix' => $confusion_matrix,
            'total_samples' => count($all_predictions),
            'k_value' => $k,
            'fold_count' => $folds
        ];
    }
    
    // Calculate accuracy from predictions
    private function calculateAccuracy($predictions) {
        if (empty($predictions)) return 0;
        
        $correct = 0;
        foreach ($predictions as $pred) {
            if ($pred['actual'] == $pred['predicted']) {
                $correct++;
            }
        }
        
        return $correct / count($predictions);
    }
    
    // Calculate confusion matrix
    private function calculateConfusionMatrix($predictions) {
        if (empty($predictions)) return null;
        
        // Get all unique classes
        $classes = [];
        foreach ($predictions as $pred) {
            if (!in_array($pred['actual'], $classes)) {
                $classes[] = $pred['actual'];
            }
            if ($pred['predicted'] && !in_array($pred['predicted'], $classes)) {
                $classes[] = $pred['predicted'];
            }
        }
        sort($classes);
        
        // Initialize matrix
        $matrix = [];
        foreach ($classes as $actual) {
            $matrix[$actual] = [];
            foreach ($classes as $predicted) {
                $matrix[$actual][$predicted] = 0;
            }
        }
        
        // Fill matrix
        foreach ($predictions as $pred) {
            if ($pred['predicted']) {
                $matrix[$pred['actual']][$pred['predicted']]++;
            }
        }
        
        // Calculate metrics for each class
        $metrics = [];
        foreach ($classes as $class) {
            $tp = $matrix[$class][$class]; // True Positive
            $fp = 0; // False Positive
            $fn = 0; // False Negative
            $tn = 0; // True Negative
            
            // Calculate FP, FN, TN
            foreach ($classes as $other_class) {
                if ($other_class != $class) {
                    $fp += $matrix[$other_class][$class]; // Others predicted as this class
                    $fn += $matrix[$class][$other_class]; // This class predicted as others
                    foreach ($classes as $third_class) {
                        if ($third_class != $class) {
                            $tn += $matrix[$other_class][$third_class];
                        }
                    }
                }
            }
            
            // Calculate precision, recall, f1-score
            $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
            $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
            $f1_score = ($precision + $recall) > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;
            
            $metrics[$class] = [
                'precision' => $precision,
                'recall' => $recall,
                'f1_score' => $f1_score,
                'support' => $tp + $fn // Total actual instances of this class
            ];
        }
        
        return [
            'matrix' => $matrix,
            'classes' => $classes,
            'metrics' => $metrics
        ];
    }
    
    // Standalone confusion matrix calculation (without cross validation)
    public function calculateConfusionMatrixStandalone($k = 3) {
        if (empty($this->training_data)) {
            return null;
        }
        
        $predictions = [];
        
        // Use Leave-One-Out approach for each training sample
        foreach ($this->training_data as $index => $test_item) {
            // Create training set without current item
            $temp_training = $this->training_data;
            unset($temp_training[$index]);
            $temp_training = array_values($temp_training); // Re-index
            
            // Temporarily replace training data
            $original_training = $this->training_data;
            $this->training_data = $temp_training;
            
            $input_data = [
                'harga' => $test_item['harga'],
                'standar' => $test_item['standar'],
                'kaca' => $test_item['kaca'],
                'double_visor' => $test_item['double_visor'],
                'ventilasi_udara' => $test_item['ventilasi_udara'],
                'berat' => $test_item['berat'],
                'wire_lock' => $test_item['wire_lock']
            ];
            
            $prediction = $this->predict($input_data, $k);
            
            $predictions[] = [
                'actual' => $test_item['kelas'],
                'predicted' => $prediction ? $prediction['predicted_class'] : null,
                'confidence' => $prediction ? $prediction['confidence'] : 0,
                'test_item' => $test_item
            ];
            
            // Restore original training data
            $this->training_data = $original_training;
        }
        
        $accuracy = $this->calculateAccuracy($predictions);
        $confusion_matrix = $this->calculateConfusionMatrix($predictions);
        
        return [
            'predictions' => $predictions,
            'accuracy' => $accuracy,
            'confusion_matrix' => $confusion_matrix,
            'method' => 'Leave-One-Out',
            'k_value' => $k,
            'total_samples' => count($predictions)
        ];
    }
}
?>