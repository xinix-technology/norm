<?php $uniqid = uniqid('nreference-list-') ?>
<div id="<?php echo $uniqid ?>" class="field-group">
    <template class="tpl">
        <?php echo $self->render('__norm__/nreference/input', [
            'self' => $self,
            'name' => $self['name'] . '[]',
            'value' => '',
            // 'entry' => '',
        ]); ?>
    </template>

    <div class="container">
        <?php if (!empty($value)): ?>
        <?php foreach ($value as $k => $v): ?>
            <?php echo $self->render('__norm__/nreference/input', [
                'self' => $self,
                'name' => $self['name'] . '[]',
                'value' => $v,
                // 'entry' => '',
            ]); ?>
        <?php endforeach ?>
        <?php endif ?>

        <?php echo $self->render('__norm__/nreference/input', [
            'self' => $self,
            'name' => $self['name'] . '[]',
            'value' => '',
            // 'entry' => '',
        ]); ?>
    </div>
  <a href="#" class="button">Add</a>

  <script type="text/javascript">
  (function () {
    var component = document.getElementById('<?php echo $uniqid ?>');
    var container = component.querySelector('.container');

    component.querySelector('.button').addEventListener('click', function (evt) {
      evt.preventDefault();
      var tpl = component.querySelector('.tpl').cloneNode(true);
      container.appendChild(tpl.content);
    });
  })();
  </script>
</div>
