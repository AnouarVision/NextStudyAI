document.addEventListener('DOMContentLoaded', () => {
    const itemsPerPage = 5;
    const grid = document.getElementById('resources-grid');
    const articles = Array.from(grid.children);
    const pagination = document.getElementById('pagination');

    let currentPage = 1;
    const totalPages = Math.ceil(articles.length / itemsPerPage);

    function showPage(page) {
        currentPage = page;
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        articles.forEach((a, i) => {
            a.style.display = (i >= start && i < end) ? 'block' : 'none';
        });

        renderPagination();
    }

    function renderPagination() {
        pagination.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '‹';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => showPage(currentPage - 1);
        pagination.appendChild(prevBtn);

        const current = document.createElement('span');
        current.textContent = ` Pagina ${currentPage} di ${totalPages} `;
        current.style.margin = '0 10px';
        pagination.appendChild(current);

        const nextBtn = document.createElement('button');
        nextBtn.textContent = '›';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => showPage(currentPage + 1);
        pagination.appendChild(nextBtn);
    }

    showPage(1);
});