<select name="<?php echo $self['name'] ?>">
    <option value="0" <?php echo !$value ? 'selected' : '' ?>>False</option>
    <option value="1" <?php echo $value ? 'selected' : '' ?>>True</option>
</select>