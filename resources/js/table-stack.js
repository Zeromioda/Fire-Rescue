// Copy each column header onto its cells as data-label, used by the
// .table-stack styles in app.css to show rows as labelled cards on phones.
export function initTableStacks(root = document) {
    root.querySelectorAll('table.table-stack').forEach((table) => {
        const labels = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

        table.querySelectorAll('tbody tr').forEach((row) => {
            let column = 0;
            [...row.children].forEach((cell) => {
                if (cell.tagName !== 'TD') return;
                if (!cell.hasAttribute('data-label')) cell.setAttribute('data-label', labels[column] ?? '');
                column += cell.colSpan || 1;
            });
        });
    });
}
