<?php echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content">
    <?php echo $content_top; ?>
    <div class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
            <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>">
                <?php echo $breadcrumb['text']; ?>
            </a>
        <?php } ?>
    </div>
    <h1><?php echo $form_heading; ?></h1>
    <div>
        <form name=" frm" method="POST" action="">
            <table border="0">
                <tr>
                    <td width="30%"><?php echo $first_value; ?></td>
                    <td width="70%"><input type="text" name="first_value"></td>
                </tr>
                <tr>
                    <td><?php echo $second_value; ?></td>
                    <td><input type="text" name="second_value"></td>
                </tr>
                <tr>
                    <td><?php echo $third_value; ?></td>
                    <td><input type="text" name="third_value"></td>
                </tr>
                <tr>
                    <td><?php echo $forth_value; ?></td>
                    <td><input type="text" name="forth_value"></td>
                </tr>
            </table>

            <div class="buttons">
                <div class="right">
                    <input type="submit" class="button" value="<?php echo $button_continue; ?>">
                </div>
            </div>
        </form>
    </div>
</div>
<?php echo $content_bottom; ?>
<?php echo $footer; ?>