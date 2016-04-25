<?php
$uniqid = uniqid('array-');
?>
<div id="<?php echo $uniqid ?>">
  <template class="tpl">
    <input type="text" name="<?php echo $self['name'] ?>[]" value="">
  </template>
  <div class="container">
    <?php if (!empty($value)): ?>
    <?php foreach ($value as $k => $v): ?>
    <input type="text" name="<?php echo $self['name'] ?>[]" value="<?php echo $v ?>">
    <?php endforeach ?>
    <?php endif ?>
    <input type="text" name="<?php echo $self['name'] ?>[]" value="">
  </div>
  <a href="#" class="button">Add</a>

  <script type="text/javascript">
  (function() {
    var component = document.getElementById('<?php echo $uniqid ?>');

    component.querySelector('.button').addEventListener('click', function(evt) {
      evt.preventDefault();
      var tpl = component.querySelector('.tpl').cloneNode(true);
      var container = component.querySelector('.container');
      container.appendChild(tpl.content);
    });
  })();
  </script>
</div>