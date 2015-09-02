<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8" />
    <title>404</title>
    <style type="text/css">
        html,body{margin:0;padding:0;height:100%;width:100%;font-family:'微软雅黑';}
        table{height:100%;width:100%;border-spacing:0;border-collapse:collapse;background-color:#414141;}
        td{width:100%;padding:0;}
        p,a{padding:0;margin:0;}
        table a{display:inline-block;height:26px;width:80px;line-height:26px;font-size:12px;text-decoration:none;border-radius:12px;background:#222;color:#D0D0D0;}
        table a:hover{background:#D0D0D0;color:#222;}
        #powered:hover{color:#FFF !important;text-decoration:underline !important;}
    </style>
</head>
<body>
    <table>
        <tr>
            <td valign="bottom" align="center" height="38%">
                <div style="position:relative;width:300px;height:88px;margin:0 auto;">
                    <div style="position:absolute;top:0px;width:300px;height:88px;line-height:88px;font-size:88px;font-weight:bold;color:#D0D0D0;text-shadow:0 3px 8px #2A2A2A;">404</div>
                </div>
            </td>
        </tr>
        <tr>
            <td valign="top" align="center" height="62%">
                <div style="width:300px;height:20px;"></div>
                <p style="font-size:14px;padding:0;height:40px;line-height:40px;color:#D0D0D0;text-shadow:0 2px 3px #555555;letter-spacing:1px;">Oh...非常抱歉，您访问的页面找不到啦……</p>
                <p style="height:26px;margin-top:24px;">
                    <a href="<?php echo _ROOT , '/'; ?>">回到首页</a>
                    <a href="javascript:;" onclick="window.opener=null;window.open('','_self');window.close();">关闭页面</a>
                </p>
            </td>
        </tr>
    </table>
    <a id="powered" href="javascript:;" style="display:block;position:absolute;right:20px;bottom:0;line-height:40px;color:#888;font-size:12px;text-decoration:none;">Powered by Peas Framework <?php echo Peas::VERSION ?></a>
</body>
</html>