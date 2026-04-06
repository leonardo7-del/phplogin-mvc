document.addEventListener('DOMContentLoaded', function () {
    var otpInput = document.querySelector('input[name="otp"]');

    if (!otpInput) {
        return;
    }

    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });
});
