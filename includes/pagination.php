<?php
/** Work out page numbers for a list. Reads ?page= from the URL. */
function paginate(int $total, int $per = 8): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page  = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
    return ['page' => $page, 'pages' => $pages, 'per' => $per, 'offset' => ($page - 1) * $per, 'total' => $total];
}

/** Bootstrap pagination links that keep the current search/filter in the URL. */
function pagination_links(array $p): string
{
    if ($p['pages'] <= 1) return '';
    $link = function (int $n, string $label, bool $active = false, bool $disabled = false): string {
        $q = $_GET; $q['page'] = $n;
        return '<li class="page-item' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '') . '">' .
               '<a class="page-link" href="?' . e(http_build_query($q)) . '">' . $label . '</a></li>';
    };
    $h = '<nav aria-label="Pages"><ul class="pagination pagination-sm mb-0">';
    $h .= $link($p['page'] - 1, '&laquo;', false, $p['page'] === 1);
    for ($i = 1; $i <= $p['pages']; $i++) $h .= $link($i, (string) $i, $i === $p['page']);
    $h .= $link($p['page'] + 1, '&raquo;', false, $p['page'] === $p['pages']);
    return $h . '</ul></nav>';
}