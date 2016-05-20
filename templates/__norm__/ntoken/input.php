
<!-- ntoken -->
<?php $uniqueId = uniqid('ntoken-') ?>

<div id="<?php echo $uniqueId ?>" class="field-group">
    <input type="text"
        name="<?php echo $self['name'] ?>"
        value="<?php echo $value ?>"
        placeholder="<?php echo $self->translate($self['label']) ?>"
        />
    <a href="#" class="button">Generate</a>
</div>

<script type="text/javascript">
(function() {
    'use strict';

    var containerEl = document.querySelector("#<?php echo $uniqueId ?>");

    containerEl.querySelector('a').addEventListener("click", function(evt) {
        evt.preventDefault();
        evt.stopImmediatePropagation();

        containerEl.querySelector('input').value = (function makeid(len) {
            len = len || 5;
            var text = "";
            var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for( var i=0; i < len; i++ )
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        })(20);
    });
})();
</script>
