// Show alert message and auto hide
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
}

// Payment method selection
function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    document.getElementById('selected_method').value = method;
    
    // Show/hide payment fields
    if (method === 'card') {
        document.getElementById('card_fields').style.display = 'block';
        document.getElementById('mobile_fields').style.display = 'none';
    } else {
        document.getElementById('card_fields').style.display = 'none';
        document.getElementById('mobile_fields').style.display = 'block';
    }
}

// Process payment
function processPayment() {
    const method = document.getElementById('selected_method').value;
    
    if (method === 'card') {
        const cardNumber = document.getElementById('card_number').value;
        const expiry = document.getElementById('expiry').value;
        const cvv = document.getElementById('cvv').value;
        
        if (!cardNumber || !expiry || !cvv) {
            showAlert('Please fill all card details', 'error');
            return false;
        }
    } else {
        const transactionId = document.getElementById('transaction_id').value;
        if (!transactionId) {
            showAlert('Please enter transaction ID', 'error');
            return false;
        }
    }
    
    showAlert('Payment Successful!', 'success');
    return true;
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    for (let input of inputs) {
        if (!input.value.trim()) {
            showAlert(`Please fill ${input.name || input.id}`, 'error');
            input.focus();
            return false;
        }
    }
    return true;
}

// Confirm action
function confirmAction(message) {
    return confirm(message || 'Are you sure?');
}

// Filter table rows
function filterTable(inputId, tableId, columnIndex) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cell = rows[i].getElementsByTagName('td')[columnIndex];
        if (cell) {
            const text = cell.textContent || cell.innerText;
            rows[i].style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}

// Load dashboard data with AJAX
function loadDashboardStats() {
    fetch('admin/dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total_resources').innerText = data.total_resources;
            document.getElementById('total_clients').innerText = data.total_clients;
            document.getElementById('total_requests').innerText = data.total_requests;
            document.getElementById('pending_payments').innerText = data.pending_payments;
        })
        .catch(error => console.error('Error:', error));
}

// Auto refresh every 30 seconds if on dashboard
if (window.location.pathname.includes('dashboard')) {
    setInterval(() => {
        location.reload();
    }, 30000);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('AgriRMS System Ready');
});