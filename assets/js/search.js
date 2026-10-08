// Live client-side filtering of the search results table (in addition to server-side search)
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('liveFilter');
    if (!input) return;
    input.addEventListener('keyup', function () {
        var term = input.value.toLowerCase();
        document.querySelectorAll('#resultsTable tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
});
