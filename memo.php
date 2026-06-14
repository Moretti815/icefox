<?php
/**
 * Icefox Memo API
 *
 * 朋友圈数据接口
 * 提供结构化JSON数据，支持图片/视频、广告标识、置顶、标签、位置、跳转链接等
 *
 * @package icefox
 * @author 小胖脸
 * @version 1.0.0
 * @link https://xiaopanglian.com
 *
 * 直接访问: /usr/themes/icefox/memo.php
 * 路由访问: /v1/memo (需配合 themeInit 拦截)
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    $root = dirname(__DIR__, 3);
    $configFile = $root . DIRECTORY_SEPARATOR . 'config.inc.php';

    if (is_file($configFile)) {
        require_once $configFile;
        try {
            Typecho_Widget::widget('Widget_Init');
        } catch (Exception $e) {
            // Widget_Init may already have been called; ignore
        }
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => -1, 'message' => 'Typecho environment not found at: ' . $root]);
        exit;
    }
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

handleMemoApi();
