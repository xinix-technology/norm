
<input type="datetime-local"
    name="<?php echo $self['name'] ?>"
    value="<?php echo ($value ? $value->format("Y-m-d\TH:i") : '') ?>"
    placeholder="<?php echo $self['label'] ?>"
    autocomplete="off" />
