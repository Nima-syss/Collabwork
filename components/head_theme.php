<?php
/**
 * Shared fonts + base styles.
 *
 * @var string $themeCssDir Path to assets/css/ from the HTML document, with trailing slash.
 * @var array<int,string>|null $themeExtraLinks Inserted after landing.css, before app-shell.css.
 * @var array<int,string>|null $themeTailLinks Inserted after app-shell.css (e.g. page overrides).
 */
$themeCssDir = isset($themeCssDir) ? $themeCssDir : '../assets/css/';
$themeExtraLinks = isset($themeExtraLinks) && is_array($themeExtraLinks) ? $themeExtraLinks : [];
$themeTailLinks = isset($themeTailLinks) && is_array($themeTailLinks) ? $themeTailLinks : [];
$fontHref = 'https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap';

/** @var string $_ew_css_fs_base Absolute path to assets/css/ */
$_ew_css_fs_base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR;
$ewCssV = static function (string $file) use ($_ew_css_fs_base): int {
    $p = $_ew_css_fs_base . $file;
    return is_file($p) ? (int) filemtime($p) : 1;
};
$ewCssHref = static function (string $href, int $v): string {
    $sep = strpos($href, '?') !== false ? '&' : '?';
    return $href . $sep . 'v=' . $v;
};
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?php echo htmlspecialchars($fontHref); ?>" rel="stylesheet">
<link rel="stylesheet" href="<?php echo htmlspecialchars($ewCssHref($themeCssDir . 'style.css', $ewCssV('style.css'))); ?>">
<link rel="stylesheet" href="<?php echo htmlspecialchars($ewCssHref($themeCssDir . 'landing.css', $ewCssV('landing.css'))); ?>">
<?php foreach ($themeExtraLinks as $href): ?>
<?php
    if (preg_match('/[?&]v=\d/', $href)) {
        $hrefOut = $href;
    } else {
        $pathOnly = parse_url($href, PHP_URL_PATH);
        if ($pathOnly === null || $pathOnly === false || $pathOnly === '') {
            $pathOnly = preg_replace('/\?.*/', '', $href);
        }
        $bn = basename($pathOnly);
        $vx = ($bn !== '' && is_file($_ew_css_fs_base . $bn)) ? (int) filemtime($_ew_css_fs_base . $bn) : 1;
        $hrefOut = $ewCssHref($href, $vx);
    }
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($hrefOut); ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($ewCssHref($themeCssDir . 'app-shell.css', $ewCssV('app-shell.css'))); ?>">
<?php foreach ($themeTailLinks as $href): ?>
<?php
    if (preg_match('/[?&]v=\d/', $href)) {
        $hrefOut = $href;
    } else {
        $pathOnly = parse_url($href, PHP_URL_PATH);
        if ($pathOnly === null || $pathOnly === false || $pathOnly === '') {
            $pathOnly = preg_replace('/\?.*/', '', $href);
        }
        $bn = basename($pathOnly);
        $vx = ($bn !== '' && is_file($_ew_css_fs_base . $bn)) ? (int) filemtime($_ew_css_fs_base . $bn) : 1;
        $hrefOut = $ewCssHref($href, $vx);
    }

    
?>

<link rel="stylesheet" href="<?php echo htmlspecialchars($hrefOut); ?>">
<?php endforeach; ?>
