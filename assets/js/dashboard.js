function toggleUserDropdown() {
    const menu = document.getElementById('user-dropdown-menu');
    const arrow = document.querySelector('.sidebar-user .dropdown-arrow');
    const isVisible = menu.style.display === 'block';
    menu.style.display = isVisible ? 'none' : 'block';
    arrow.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('user-dropdown-menu');
    const userBlock = document.querySelector('.sidebar-user');
    if (!userBlock.contains(e.target) && !menu.contains(e.target)) {
        menu.style.display = 'none';
        document.querySelector('.sidebar-user .dropdown-arrow').style.transform = 'rotate(0deg)';
    }
});
