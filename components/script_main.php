<?php
/**
 * Single load of app script with cache-bust (avoids stale script.js after edits).
 *
 * Optional before include:
 *   $ewScriptHref — URL path to script.js from the HTML document (default: ../assets/js/script.js)
 */
if (!isset($ewScriptHref) || !is_string($ewScriptHref) || $ewScriptHref === '') {
    $ewScriptHref = '../assets/js/script.js';
}
$ewScriptFs = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'script.js';
$ewScriptVer = is_file($ewScriptFs) ? (int) filemtime($ewScriptFs) : 1;
$ewSep = strpos($ewScriptHref, '?') !== false ? '&' : '?';
$ewScriptSrcOut = $ewScriptHref . $ewSep . 'v=' . $ewScriptVer;
unset($ewScriptHref);
?>
<script src="<?php echo htmlspecialchars($ewScriptSrcOut, ENT_QUOTES, 'UTF-8'); ?>"></script>
