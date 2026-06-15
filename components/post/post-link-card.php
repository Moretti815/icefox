<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$linkCard = getPostLinkCard($this->cid);
if (empty($linkCard)) {
    return;
}

$title = htmlspecialchars($linkCard['title'], ENT_QUOTES, 'UTF-8');
$icon = htmlspecialchars($linkCard['icon'], ENT_QUOTES, 'UTF-8');
$url = trim($linkCard['url']);
$href = $url !== '' ? $url : $linkCard['link'];
$description = trim($linkCard['description']);
if ($description === '') {
    $description = getDisplayDomainFromUrl($url);
}
if ($description === '') {
    $description = '链接卡片';
}
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
$hrefAttr = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
$opensNewTab = $url !== '';
?>

<a class="post-link-card"
   href="<?php echo $hrefAttr; ?>"
   <?php if ($opensNewTab): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>>
    <div class="post-link-card-info">
        <div class="post-link-card-title"><?php echo $title; ?></div>
        <div class="post-link-card-description"><?php echo $description; ?></div>
    </div>
    <?php if ($icon !== ''): ?>
        <img class="post-link-card-icon"
             src="<?php echo $icon; ?>"
             alt=""
             referrerpolicy="no-referrer">
    <?php else: ?>
        <div class="post-link-card-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
            </svg>
        </div>
    <?php endif; ?>
</a>
