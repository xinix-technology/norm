<?php
use \Bono\Helper\URL;

$crit = array();
if ($criteria && $entry) {
    foreach ($criteria as $key => $v) {
        $crit[$key] = @$entry[$v];
    }
}
$foreign = Norm::factory($self['foreign']);
$entries = $foreign->find($crit);

?>
<select name="<?php echo $self['name'] ?>" data-value="<?php echo @$value ?>">
    <option value="">---</option>
    <?php foreach ($entries as $entry): ?>
        <?php
        if (is_callable($self['foreignLabel'])):
            $getLabel = $self['foreignLabel'];
            $label = $getLabel($entry);
        else:
            $label = $entry->get($self['foreignLabel']);
        endif
        ?>
        <option value="<?php echo $entry[$self['foreignKey']] ?>" <?php echo ($entry[$self['foreignKey']] === $value ? 'selected' : '') ?>><?php echo $label ?></option>
    <?php endforeach ?>
</select>
