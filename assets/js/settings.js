window.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('toastMessage');
    if(toast){
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }
});