<style>
    .postbox h3 {
        font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif;
        font-size: 15px;
        padding: 10px 10px;
        margin: 0;
        line-height: 1;
    }
    #ta-table td{
        height:30px;
    }
    .wrap h2 small{
        font-size: 12px;
        margin-left: 10px;
        color: #999;
    }
</style>
<script type="text/javascript">
    jQuery(function ($) {
        $("h3.handle").click(function () {
            $(this).next(".inside").slideToggle('fast');
        });
    });
</script>

<?php

if (isset($_POST['submit1']) && $_POST['submit1'] != '') {
    $ta_password = $_POST['ta_password'];
    $ta_image_refer = isset($_POST['ta_image_refer']) && $_POST['ta_image_refer']=="true";
    $ta_unique = isset($_POST['ta_unique']) && $_POST['ta_unique']=="true";

    update_option('ta_password', $_POST['ta_password']);
    update_option('ta_image_refer', $ta_image_refer);
    update_option('ta_unique', $ta_unique);
    echo '<div id="message" class="updated fade"><p>Update Success!</p></div>';
}else{
    $ta_password = get_option('ta_password', "shenjian.io");
    $ta_image_refer = get_option('ta_image_refer', false);
    $ta_unique = get_option('ta_unique', false);
}
// 判断地址是否为内网
function isIntranet($addr){
    //验证是否是 IPv4
    if(!filter_var($addr, FILTER_VALIDATE_IP,  FILTER_FLAG_IPV4)){
        return false;
    }
    //是否为 内网
    if(!filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)){
        return true;
    }
    return false;
}

?>
<div class="wrap">
    <h2>ScrapeStorm<</h2>
    <br/>


    <form id="myform" method="post" action="admin.php?page=scrapestorm/ta-article-setting.php">
        <div class="postbox">
            <h3 class="handle" style="cursor:pointer;">Settings</h3>
            <div class="inside">
                <table width="100%" id="ta-table">
                    <tr>
                        <td width="15%">Site Address (URL):</td>
                        <td>
                            <?php if(isIntranet($_SERVER['HTTP_HOST'])){ ?>
                                <span class="error-message">(The current page is accessed through the intranet. The current public IP address or domain name cannot be obtained. The Archer does not support publishing to the intranet. Please switch to the external network to obtain the website publishing address.)</span>
                            <?php }else{ ?>
                                <input id="ta_web" type="text" name="ta_web" disabled="disabled" readonly="readonly"
                                       style="width:300px" value="<?php
                                if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
                                    echo "https://";
                                } else {
                                    echo "http://";
                                }
                                $basicWebAddress = str_replace('\\', '/', $_SERVER['HTTP_HOST'] . str_replace('/wp-admin', '', dirname($_SERVER['SCRIPT_NAME'])));
                                echo $basicWebAddress; ?>" />
                            <?php }?>
                        </td>
                    </tr>

                    <tr>
                        <td>Password:</td>
                        <td><input type="text" name="ta_password" style="color:black;width:300px" value="<?php echo $ta_password; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>Image Redirect:</td>
                        <td><input type="checkbox" name="ta_image_refer" value="true" <?php if($ta_image_refer == true) echo "checked='checked'" ?> />
                        </td>
                    </tr>
                    <tr>
                        <td>Unique Title:</td>
                        <td><input type="checkbox" name="ta_unique" value="true" <?php if($ta_unique == true) echo "checked='checked'" ?> />
                            <small> (Don't insert the same title)</small>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="submit" class="button-primary"  name="submit1"  value="save" /></td>
                        <td>

                        </td>
                    </tr>

                </table>
            </div>
        </div>
    </form>

    <div class="postbox">
        <h3 class="handle" style="cursor:pointer;">Plugin Info</h3>
        <div class="inside">
            <table width="100%" id="ta-table">
                <tr>
                    <td width="15%">ScrapeStorm:</td>
                    <td><a href="http://www.scrapestorm.com" target="_blank">http://www.scrapestorm.com/</a></td>
                </tr>
                <tr>
                    <td>Plugin Version:</td>
                    <td>WordPress Plugin v4.2.4</a></a></td>
                </tr>
            </table>
        </div>
    </div>
</div>