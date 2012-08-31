<?php
$base = base_url();
$attributes = array('class' => 'span-15', 'id' => 'login');
?>


<?php echo form_open("auth/login", $attributes); ?>

<fieldset>
    <legend>Login form</legend>
    <?
    $v_errors = validation_errors('<div>', '</div>');
    if (!empty($v_errors)) {
        echo "<div class=\"error\">";
        echo $v_errors;
        echo "</div>";
    }
    ?>
    <ol>
        <li>
            <?php
            echo form_label('Username', 'username');
            echo form_input('username');
            ?>
        </li>
        <li>

            <label for="password">Password</label>
            <?php
            echo form_password('password');
            ?>
        </li>
    </ol>
    <div class="buttons">

        <?php
        if (!empty($shib_url)) {
            echo anchor($shib_url, '<button type="button" name="faderated" value="fadetate" class="btn" onclick="window.open(\''.$shib_url.'\',\'_self\')">Federated Access</button>');
        }
        ?> 
        <button type="submit" name="submit" value="Login" class="btn">Local Authentication</button>
    </div>
</fieldset>
<?php echo form_close(); ?>
    
