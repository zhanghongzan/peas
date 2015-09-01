<meta charset="utf-8">
<div id="debugCss" style="display:none;">
    html,body{height:100%;width:100%;}
    html,body,div,span,object,iframe,p,a,em,img,strong,h1,h2,h3,h4,h5,h6,u,i,center,dl,dt,dd,ul,ol,li,fieldset,legend,form,label{margin:0;padding:0;border:0;outline:0;font-size:100%;}
    body{font:12px/1.5 tahoma,'微软雅黑',arial,'宋体';}
    ul,ol{list-style:none;}
    a{text-decoration:none;color:#333;}

    #debug-main{padding:80px 50px;font-size:14px;}
    #debug-main table{width:100%;margin:0 auto;}
    #debug-main td{padding:8px;height:100px;}
    #debug-main a{display:block;text-align:center;height:100px;line-height:100px;}
    #debug-main .line2{line-height:24px;padding-top:26px;height:74px;}
    #debug-main .line3{line-height:24px;padding-top:14px;height:86px;}

    .red{background:#FF3C00;color:#FFF;font-weight:bold;}
    .red:hover{background:#FF602F;}
    .green{background:green;color:#FFF;font-weight:bold;}
    .green:hover{background:#00A800;}
    .orange{background:#FF6D1D;color:#FFF;}
    .orange:hover{background:#FF8B35;}
    .darkGreen{background:#65BF10;color:#FFF;cursor:default;}
    .gray{background:#809076;color:#FFF;}
    .gray:hover{background:#A3BD92;}
    .blue{background:#00BCE2;color:#FFF;}
    .blue:hover{background:#2CD6F8;}
    .blue1{background:#00BCE2;color:#FFF;cursor:default;}
    .yellow{background:#CBE709;color:#4D4B56;}
    .yellow:hover{background:#DEF82A;}
    .purple{background:#BC3BC2;color:#FFF;}
    .purple:hover{background:#E652ED;}
    .purple1{background:#BC3BC2;color:#FFF;cursor:default;}

    #debug-toolbar,#debug-2,#debug-3,#debug-4,#debug-5,#debug-6{display:none;padding:0px 50px 30px;}

    #debug-toolbar{height:30px;padding:40px 50px 10px 55px;}
    #debug-toolbar a{font-size:14px;font-weight:bold;float:left;margin-right:20px;}

    .table{width:100%;border-collapse:collapse;color:#333;}
    .table th,.table td{border:1px solid #BBD6F1;font-size:14px;line-height:24px;}
    .table th{background:#F2F8FF;}
    .table thead th{line-height:28px;background:#E8F3FF;}
    .table .t1{background:#F2F8FF;text-align:center;}
    .table .t2{background:#FFFFFF;padding-left:15px;}
    .table .dark{background:#E7F1FF;}

    #debug-4 .t1{text-align:left;padding-left:15px;}
    #debug-4 .t2{padding-left:0;text-align:center;}

    .peas-notice{height:30px;line-height:30px;padding-right:2px;text-align:right;color:#666;}
</div>
<div id="debugPage" style="display:none;">
    <script type="text/javascript">
        function debugShowDetail(id, num) {
            if (num <= 0) {
                return;
            }
            debugSetDisplay(new Array('debug-main', 'debug-2', 'debug-3', 'debug-4', 'debug-5', 'debug-6'), 'none');
            debugSetDisplay(new Array('debug-' + id, 'debug-toolbar'), 'block');
        }
        function debugShowAll() {
            debugSetDisplay(new Array('debug-toolbar', 'debug-2', 'debug-3', 'debug-4', 'debug-5', 'debug-6'), 'block');
            debugSetDisplay(new Array('debug-main'), 'none');
        }
        function debugShowIndex() {
            debugSetDisplay(new Array('debug-toolbar', 'debug-2', 'debug-3', 'debug-4', 'debug-5', 'debug-6'), 'none');
            debugSetDisplay(new Array('debug-main'), 'block');
        }
        function debugSetDisplay(arr, display) {
            var obj = null;
            for (var i = 0; i < arr.length; i ++) {
            	obj = document.getElementById(arr[i]);
            	if (obj != null) {
            		obj.style.display = display;
            	}
            }
        }
    </script>
    <div id="debug-main">
        <table>
            <tr>
                <td width="33%"><?php $exceptions = isset($exceptions) ? $exceptions : array(); $errors = isset($errors) ? $errors : array(); ?>
                    <a href="javascript:;" onclick="debugShowAll();" class="<?php echo count($exceptions) + count($errors) > 0 ? 'red' : 'green'; ?>">查看全部</a>
                </td>
                <td width="33%">
                    <a href="javascript:;" onclick="debugShowDetail(2, <?php echo count($exceptions) + count($errors);?>);" class="line2 <?php echo count($exceptions) + count($errors) > 0 ? 'orange' : 'darkGreen'; ?>">错误（<?php echo count($errors); ?>）<br />异常（<?php echo count($exceptions); ?>）</a>
                </td>
                <td width="33%"><?php $dbQueryNum = isset($dbQueryNum) ? $dbQueryNum : 0; $dbWriteNum = isset($dbWriteNum) ? $dbWriteNum : 0;?>
                    <a href="javascript:;" onclick="debugShowDetail(3, <?php echo $dbQueryNum + $dbWriteNum;?>);" class="<?php echo $dbQueryNum + $dbWriteNum > 0 ? 'blue' : 'blue1'; ?> line3">SQL<br />读取：<?php echo $dbQueryNum; ?><br />写入：<?php echo $dbWriteNum; ?></a>
                </td>
            </tr>
            <tr>
                <td><?php $debugMark = isset($debugMark) ? $debugMark : array();$allTimeUsed = isset($allTimeUsed) ? $allTimeUsed : 0; $allMemUsed = isset($allMemUsed) ? $allMemUsed : 0; ?>
                    <a href="javascript:;" onclick="debugShowDetail(4, <?php echo count($debugMark); ?>);" class="gray line3">调试点（<?php echo count($debugMark); ?>）<br />耗时：<?php echo $allTimeUsed;?>s<br />占用内存：<?php echo $allMemUsed;?>KB</a>
                </td>
                <td><?php $varsNum = isset($varsNum) ? $varsNum : 0;?>
                    <a href="javascript:;" onclick="debugShowDetail(5, <?php echo $varsNum; ?>);" class="<?php echo $varsNum > 0 ? 'purple' : 'purple1'; ?>">模板变量（<?php echo $varsNum; ?>）</a>
                </td>
                <td><?php $allLoad = isset($allLoad) ? $allLoad : array();?>
                    <a href="javascript:;" onclick="debugShowDetail(6, <?php echo count($allLoad); ?>);" class="yellow">加载文件（<?php echo count($allLoad); ?>）</a>
                </td>
            </tr>
        </table>
    </div>

    <div id="debug-toolbar">
        <a href="javascript:;" onclick="debugShowIndex();" class="back">返回</a>
        <a href="javascript:;" onclick="debugShowAll();" class="all">查看全部</a>
    </div>
    <?php if (count($errors) > 0 || count($exceptions) > 0) : ?>
    <div id="debug-2">
        <?php if (count($errors) > 0) : ?><table class="table">
            <thead>
                <tr>
                    <th colspan="2">错误（<?php echo count($errors); ?>）</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="10%">编号</th>
                    <th width="90%">内容</th>
                </tr>
                <?php foreach ($errors as $id => $err):?><tr>
                    <td class="t1"><?php echo $id; ?></td>
                    <td class="t2"><?php echo $err; ?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table><?php endif;?><?php if (count($exceptions) > 0) : ?>
        <table class="table" <?php if (count($errors) > 0) : ?>style="margin-top:30px;"<?php endif;?>>
            <thead>
                <tr>
                    <th colspan="2">异常（<?php echo count($exceptions); ?>）</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="10%">编号</th>
                    <th width="90%">内容</th>
                </tr>
                <?php foreach ($exceptions as $id => $except):?><tr>
                    <td class="t1"><?php echo $id;?></td>
                    <td class="t2"><?php echo $except;?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table><?php endif;?>
    </div>
    <?php endif;?><?php if ($dbQueryNum + $dbWriteNum > 0) : ?>
    <div id="debug-3">
        <table class="table">
            <thead>
                <tr>
                    <th colspan="3">SQL（读取：<?php echo $dbQueryNum; ?>，写入：<?php echo $dbWriteNum; ?>）</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="10%">次序</th>
                    <th width="25%">耗时</th>
                    <th width="65%">SQL</th>
                </tr><?php $sqls = isset($sqls) ? $sqls : array(); foreach ($sqls as $id => $sql):?>
                <tr>
                    <td class="t1"><?php echo $id;?></td>
                    <td class="t2 <?php echo $sql[1] > 0.5 ? 'dark' : '';?>"><?php echo $sql[1];?></td>
                    <td class="t2"><?php echo $sql[0];?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table>
    </div><?php endif;?>

    <div id="debug-4">
        <table class="table">
            <thead>
                <tr>
                    <th colspan="4">总耗时：<?php echo $allTimeUsed;?>s&nbsp;&nbsp;&nbsp;占用内存：<?php echo $allMemUsed;?>KB&nbsp;&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="25%">标记</th>
                    <th width="25%">时间</th>
                    <th width="25%">内存</th>
                    <th width="25%">内存峰值</th>
                </tr><?php foreach ($debugMark as $id => $mark):
                    if ($mark['mark'] == '_peas_debug_begin') {
                        $mark['mark'] = '开始';
                    } else if ($mark['mark'] == '_peas_debug_end') {
                        $mark['mark'] = '结束';
                    }?>
                <tr>
                    <td class="t1"><?php echo $mark['mark'];?></td>
                    <td class="t2"><?php echo $mark['timeUsed'];?></td>
                    <td class="t2"><?php echo $mark['memUsed'];?></td>
                    <td class="t2"><?php echo $mark['peakUsed'];?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table>
        <div class="peas-notice">注：以上值均为与上一标记的区间值</div>
    </div>

    <?php if ($varsNum > 0) : ?><div id="debug-5">
        <table class="table">
            <thead>
                <tr>
                    <th colspan="3">模板变量</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="10%">&nbsp;</th>
                    <th width="15%">变量名</th>
                    <th width="75%">值</th>
                </tr><?php $j = 0; foreach ($vars as $key => $val): $j++;?>
                <tr>
                    <td class="t1"><?php echo $j;?></td>
                    <td class="t2">$<?php echo $key;?></td>
                    <td class="t2"><?php echo Peas\Kernel\Debug::showArrayToHtml($val);?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table>
    </div><?php endif;?>

    <div id="debug-6">
        <table class="table">
            <thead>
                <tr>
                    <th colspan="2">加载文件（<?php echo count($allLoad); ?>）</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th width="10%">顺序</th>
                    <th width="90%">文件路径</th>
                </tr><?php foreach ($allLoad as $id => $file):?>
                <tr>
                    <td class="t1"><?php echo $id + 1;?></td>
                    <td class="t2"><?php echo $file;?></td>
                </tr><?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
    var PfD = {
        debugWidth  : window.screen.availWidth  * 0.60,
        debugHeight : window.screen.availHeight * 0.60,
        debugLeft   : window.screen.availWidth  * 0.20,
        debugTop    : window.screen.availHeight * 0.16
    };
    var PfDW = null;
    function showDebugWindow() {
        PfDW = window.open("", "DisplayWindow", "toolbar=no,menubar=no,location=no,scrollbars=yes"
            + ",width="  + PfD.debugWidth
            + ",height=" + PfD.debugHeight
            + ",left="   + PfD.debugLeft
            + ",top="    + PfD.debugTop);
        PfDW.document.body.innerHTML = '';
        PfDW.document.write('<!DOCTYPE HTML><html><head><meta charset="utf-8">');
        PfDW.document.write("<title>Debug Console - Peas Framework</title>");
        PfDW.document.write('<style type="text/css">');
        PfDW.document.write(document.getElementById('debugCss').innerHTML);
        PfDW.document.write("</style></head><body>");
        PfDW.document.write(document.getElementById('debugPage').innerHTML);
        PfDW.document.write("</body></html>");
    }
    <?php $openWindow = isset($openWindow) ? $openWindow : FALSE; if ($openWindow) :?>showDebugWindow();<?php endif;?>

    document.onkeydown = function (event) {
    	e = event ? event : (window.event ? window.event : null);
        if (e != null && e.ctrlKey && e.keyCode == 77) { // ctrl + 1
            if (PfDW == null || (PfDW != null && PfDW.closed)) {
            	showDebugWindow();
            }
        }
    };
</script>
