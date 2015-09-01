<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8" />
    <title>500</title>
    <style type="text/css">
        html,body{margin:0;padding:0;height:100%;width:100%;font-family:'微软雅黑';background-color:#FBFBFB;}
        table{height:100%;width:100%;border-spacing:0;border-collapse:collapse;}
        td{width:100%;padding:0;}
        p,a{padding:0;margin:0;}
        table a{line-height:26px;font-size:14px;text-decoration:none;color:#D0D0D0;font-weight:bold;}
        table a:hover{color:#FFF;text-decoration:underline;}
    </style>
</head>
<body>
    <table>
        <tr>
            <td valign="bottom" align="center" height="40%">
                <div style="height:100px;width:500px;padding:22px 20px 0;background-color:#666;border-radius:12px 12px 0 0;">
                    <div style="border-radius:8px 8px 0 0;background-color:#CCC;height:100px;line-height:90px;width:500px;font-weight:bold;font-size:64px;color:#333;text-shadow:0 2px 3px #555555;">500</div>
                </div>
            </td>
        </tr>
        <tr>
            <td valign="top" align="center" height="60%">
                <div style="height:100px;width:500px;padding:0 20px 20px;background-color:#CCC;border-radius:0 0 12px 12px;">
                    <div style="border-radius:0 0 8px 8px;background-color:#666;height:100px;width:500px;">
                        <p style="padding-top:10px;line-height:40px;font-size:14px;color:#D0D0D0;text-shadow:0 3px 8px #2A2A2A;">您查看的页面也许遇到了些问题（500），先去别的页面看看吧~</p>
                        <p style="height:26px;">
                            <a href="<?php echo _ROOT ?>/">回到首页</a>
                            <span>&nbsp;</span>
                            <a href="javascript:;" onclick="window.opener=null;window.open('','_self');window.close();">关闭页面</a>
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <a id="powered" href="javascript:;" style="display:block;position:absolute;right:20px;bottom:0;line-height:40px;color:#888;font-size:12px;text-decoration:none;">Powered by Peas Framework <?php echo Peas::VERSION ?></a>
</body>
</html>