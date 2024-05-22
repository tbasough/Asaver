 <style>
    div#download-form {
        text-align: center;
    }
    #download-form fieldset{
        background:none;
        padding: 0px;
        border: 0px !important;
    }
    .xfurl{
        position: relative;
    }
    #download-form #url{
        background-color: #faf7fe;
        border: none;
        border-radius: 10px;
        box-shadow: none;
        width: 100%;
        padding: 25px 40px;
        font-size: 18px;
    }
    #download-form input[type="url"]:focus, #download-form input[type="url"]:focus-visible{
        outline: 0px !important;
        border: 1px solid #9a6bef !important;
    }
    #pasteBtn{
        position: absolute;
        right: 18px;
        top: 13px;
        background-color: white;
        border: 1px solid #8c55ec;
        border-radius: 10px;
        color: #8c55ec;
        font-weight: normal;
        padding: 12px 25px;
    }
    #pasteBtn object{
        vertical-align: middle;
        margin-right: 10px;
        font-weight: normal;
        width:  22px !important;
        max-width: 22px !important;
        height: 22px !important;
    }
    .xfdownload{
        text-align: center;
        margin-top: 15px;
    }
    #downloadBtn{
        background: rgb(137,87,236);
        background: linear-gradient(90deg, rgba(137,87,236,1) 0%, rgba(109,101,234,1) 46%, rgba(91,114,234,1) 100%);
        border: none;
        color: #fff;
        border-radius: 50px;
        padding: 15px 30px;
        font-size: 18px;
    }
    #downloadBtn:hover{
        background: #fff !important;
        border: 1px solid #8c55ec;
        color: #8c55ec;
    }
    .xf-video-intro {
        padding: 50px 100px;
        background-color: #faf7fe;
        border-radius: 10px;
    }
    .xf-video-intro img{
        width: 100% !important;
    }
    .xf-video-intro h2{
        font-weight: bolder;
        margin-top: 15px;
        font-size: 24px;
    }
    small#videoDuration {
        background-color: #facf32;
        padding: 10px;
        font-size: 18px;
        font-weight: bolder;
        color: black;
        border-radius: 10px;
        margin-top: 10px;
        display: inline-block;
    }
    a.xfdl {
        background: red;
        display: block;
        margin: 10px;
        padding: 10px 35px;
        border-radius: 10px;
        color: #fff;
        text-decoration: none !important;
        font-size: 20px;
    }
    span.xficon {
        vertical-align: sub;
    }
    span.xfquality {
        margin-left: 25px;
    }
    a.xfvl {
        background: #5177e9;
    }
    a.xfal {
        background: #e44e00;
    }
    .xf-video-links, .xf-audio-links, .xf-other-links{
        float: left;
    }
    .xfclear{
        clear: both;
    }
    .xf-two-col{
        width: 49%;
    }
    .xf-three-col{
        width: 32%;
    }
    .xf-format-heading {
        font-size: 26px;
        font-weight: bolder;
        color: #000;
        margin-bottom: 20px;
        margin-left: 15px;
    }
    .xf-download-links{
        margin-top: 50px;
    }
    div#result {
        margin-top: 50px;
    }
    span.xficon object {
        width: auto !important;
    }
    #result img.img-fluid {
        max-height: 530px;
    }
    @media only screen and (max-width: 600px) {
        .xfurl {
            text-align: center;
        }
        #pasteBtn{
            position: relative;
            margin-top: 15px;
            right: 0px;
            top: 0px;
            padding: 18px 40px;
            font-size: 18px;
        }
        .xf-video-intro{
            padding: 10px;
        }
        .xf-video-intro h2{
            font-size: 20px;
        }
        small#videoDuration{
            padding: 6px 10px;
            font-size: 16px;
        }
        .xf-two-col, .xf-three-col {
            width: 100%;
        }
        a.xfdl{
            padding: 10px 20px;
        }
    }
</style> 
<?php
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = generateToken();
    }
    $captchaEnabled = get_option('asr_recaptcha') == 'on';
    $onclick = $captchaEnabled ? 'onclick="recaptcha_execute()"' : '';
?>
<div class="alert alert-warning" role="alert" id="alert" style="display: none"></div>
<div id="download-form">

    <fieldset class="xfurl">
        <input id="url" type="url" name="url" class="form-control w-100"
                placeholder="<?php _e('Paste a video URL'); ?>"
                aria-label="<?php _e('Paste a video URL'); ?>">
        <button id="pasteBtn" data-name="Paste from clipboard">
            <object data="<?php echo plugin_dir_url(__FILE__).'assets/images/document-normal.svg'; ?>"> </object>
            <span id="xfpaste-text"><?php _e('Paste from clipboard'); ?></span>
        </button>
    </fieldset>
    <input id="token" type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
    <fieldset class="xfdownload">
        <button <?php echo $onclick; ?> id="downloadBtn">
            <?php _e('Download'); ?>
        </button>
    </fieldset>
    
</div>
<div id="result" style="display: none"></div>

