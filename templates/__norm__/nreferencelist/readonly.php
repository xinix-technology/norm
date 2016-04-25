
<span class="field">

<?php if (!empty($value)): ?>
    <?php foreach ($value as $key => $v): ?>
        <!-- $foreignEntry = Norm::factory($this['foreign'])->findOne(array($this['foreignKey'] => $v)); -->
        <?php if (is_string($this['foreignLabel'])): ?>
        <!-- $html .= '<code>'.$label."</code>\n"; -->
            <!-- $label = $foreignEntry[$this['foreignLabel']]; -->
        <?php elseif (is_callable($this['foreignLabel'])): ?>
            <!-- $getLabel = $this['foreignLabel']; -->
            <!-- $label = $getLabel($foreignEntry); -->
        <!-- $html .= '<code>'.$label."</code>\n"; -->
        <?php endif ?>
        <!-- $html .= '<code>'.$label."</code>\n"; -->
    <?php endforeach ?>
<?php endif ?>

</span>
