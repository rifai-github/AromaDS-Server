<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test File Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test File Upload for Master Product</h1>
    
    <form id="testForm" enctype="multipart/form-data">
        <div style="margin-bottom: 10px;">
            <label>Product ID:</label>
            <input type="number" id="product_id" name="id" value="104" required>
        </div>
        <div style="margin-bottom: 10px;">
            <label>Name: *</label>
            <input type="text" name="name" value="Test Product" required>
        </div>
        <div style="margin-bottom: 10px;">
            <label>Product Type ID: *</label>
            <input type="number" name="product_type_id" value="2" required>
        </div>
        <div style="margin-bottom: 10px;">
            <label>Product Category ID:</label>
            <input type="number" name="product_category_id" value="48">
        </div>
        <div style="margin-bottom: 10px;">
            <label>Unit: *</label>
            <input type="text" name="unit" value="pcs">
        </div>
        <div style="margin-bottom: 10px;">
            <label>Minimum Stock: *</label>
            <input type="number" name="minimum_stock" value="0">
        </div>
        <div style="margin-bottom: 10px;">
            <label>Maximum Stock: *</label>
            <input type="number" name="maximum_stock" value="100">
        </div>
        <div style="margin-bottom: 10px;">
            <label>Is Active: *</label>
            <input type="checkbox" name="is_active" value="1" checked>
        </div>
        <div style="margin-bottom: 10px;">
            <label>Description 2:</label>
            <input type="text" name="description_2" value="test desc 2">
        </div>
        <div style="margin-bottom: 10px;">
            <label><strong>Product Photo:</strong></label>
            <input type="file" name="product_photo" id="product_photo" accept="image/*">
        </div>
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">Upload</button>
    </form>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const productId = document.getElementById('product_id').value;
            
            // Add PUT method
            formData.append('_method', 'PUT');
            
            // Handle checkbox
            if (!formData.has('is_active')) {
                formData.set('is_active', '0');
            }
            
            // Debug log
            console.log('=== Form Data ===');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key + ': File - ' + value.name + ' (' + value.size + ' bytes)');
                } else {
                    console.log(key + ': ' + value);
                }
            }
            
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Uploading...';
            
            fetch('/warehouse/master-products/' + productId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                console.log('=== Server Response ===');
                console.log(result);
                resultDiv.innerHTML = '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = 'Error: ' + error.message;
            });
        });
    </script>
</body>
</html>
