
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.ingredient_check_vr');
    const progressBar = document.getElementById('progress_bar_vr');
    const progressText = document.getElementById('progress_text_vr');
    const card = progressBar.closest('.card_vr');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          
            const total = checkboxes.length;
            const checked = document.querySelectorAll('.ingredient_check_vr:checked').length;
            const percentage = Math.round((checked / total) * 100);

          
            progressBar.style.width = percentage + '%';
            progressText.innerText = percentage + '%';

            if (percentage === 100) {
                card.classList.add('finish_celebration_vr');
                progressText.innerText = "Done! 🎉";
            } else {
                card.classList.remove('finish_celebration_vr');
            }
        });
    });
});