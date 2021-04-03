<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>错误</title>
<style type="text/css">
*{ padding: 0; margin: 0; }
html{ overflow-y: scroll; }
body{ background: #fff; font-family: '微软雅黑'; color: #333; font-size: 16px; }
h1{ font-size: 32px; line-height: 48px; }
.error{ padding: 24px 48px; }
.face{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
.error .content{ padding-top: 10px}
.error .info{ margin-bottom: 12px; }
.error .info .title{ margin-bottom: 3px; color: #000; font-weight: 700; font-size: 16px;}
.error .info .text{ line-height: 24px; }
</style>
</head>
<body>
<div class="error">
    <p class="face">出错啦~</p>
    <h1><?php echo strip_tags($error['message']);?></h1>
    <div class="content">
        <p class="info"> <span >错误类型：</span> <?php echo $error['level']; ?></p>
    <?php if(isset($error['file'])) {?>
        <div class="info">
            <div class="title">
                <span>错误位置</span>
            </div>
            <div class="text">
                <p>FILE: <?php echo $error['file'] ;?> &#12288;LINE: <?php echo $error['line'];?></p>
            </div>
        </div>
    <?php }?>
    </div>
</div>
</body>
</html>