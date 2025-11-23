document.addEventListener('DOMContentLoaded', function () {
    const resetBtn = document.getElementById('resetFilterBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.location.href = 'admin.php';
        });
    }
});
