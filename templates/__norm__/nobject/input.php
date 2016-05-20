<?php $uniqid = uniqid('nobject-') ?>
<div id="<?php echo $uniqid ?>" class="field-group nobject">
    <template class="tpl">
        <div>
            <input type="text" class="property-name" value="property">
            <input type="text" name="<?php echo $self['name'] ?>[property]" value="">
        </div>
    </template>
    <div class="container">
        <?php if (!empty($value)): ?>
        <?php foreach ($value as $k => $v): ?>
        <div>
            <input type="text" class="property-name" value="<?php echo $k ?>">
            <input type="text" name="<?php echo $self['name'] ?>[<?php echo $k ?>]" value="<?php echo $v ?>">
        </div>
        <?php endforeach ?>
        <?php endif ?>
    </div>

    <a href="#" class="button">Add Property</a>

    <script type="text/javascript">
        (function() {
            'use strict';

            var component = document.getElementById('<?php echo $uniqid ?>');
            var container = component.querySelector('.container');

            component.addEventListener('change', function(evt) {
                var target = evt.target;
                if (target.classList.contains('property-name')) {
                    var value = target.value.trim();
                    if (value) {
                        target.nextElementSibling.name = '<?php echo $self["name"] ?>[' + value + ']';
                    } else {
                        console.log(target.parentElement, target.parentElement.parentElement);
                        target.parentElement.parentElement.removeChild(target.parentElement);
                        evt.stopPropagation();
                    }
                }
            });

            component.querySelector('.button').addEventListener('click', function(evt) {
                evt.preventDefault();
                var tpl = component.querySelector('.tpl').cloneNode(true);
                container.appendChild(tpl.content);
            });
        })();
    </script>
</div>

<!-- <textarea name="<?php echo $self['name'] ?>"><?php echo $value ? json_encode($value->toArray()) : '' ?></textarea> -->
