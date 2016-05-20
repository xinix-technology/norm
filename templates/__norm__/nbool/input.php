<div class="field-group">
    <label>
        <input type="radio" name="<?php echo $self['name'] ?>" value="1" <?php echo (true === $value) ? 'checked="checked"' : '' ?>>
        True
    </label>
    <label>
        <input type="radio" name="<?php echo $self['name'] ?>" value="0" <?php echo (false === $value) ? 'checked="checked"' : '' ?>>
        False
    </label>
</div>