<?php
$con = file_get_contents('login.php');

$end_html = <<<HTML
</form>
        </div>
        <div class="home-indicator">
            <div class="home-bar"></div>
        </div>
    </div>
    </div>
HTML;
$con = preg_replace('/<\/form>\s*<\/div>\s*<\/div>\s*<\/div>/s', $end_html, $con, 1);

$device_html = <<<HTML
    <div class="device-label">Solo Second Thrift &middot; Android Preview</div>
</body>
HTML;
$con = str_replace('</body>', $device_html, $con);

file_put_contents('login.php', $con);
echo "Done";
?>
