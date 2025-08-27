<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Form</h1>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" id="testForm">
                    <div class="mb-3">
                        <label class="form-label">Test Input</label>
                        <input type="text" class="form-control" name="test" required>
                    </div>
                    
                    <button type="submit" name="submit" id="testBtn" class="btn btn-primary">
                        Submit Test
                    </button>
                </form>
            </div>
        </div>
        
        <?php if ($_POST): ?>
        <div class="alert alert-success mt-3">
            Form submitted! Data: <?= print_r($_POST, true) ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Form found:', document.getElementById('testForm'));
            console.log('Button found:', document.getElementById('testBtn'));
        });
    </script>
</body>
</html>