
<?php $uniqueId = uniqid($this['name'].'_') ?>

<!-- // FIXME should be overriden for UX -->

<div style="display: flex"><input type="text"
    name="<?php echo $this['name'] ?>"
    value="<?php echo $value ?>"
    placeholder="<?php echo $self->translate($this['label']) ?>"
    autocomplete="off" /><a href="#"
            style="display: block;padding-right: 15px;line-height: 30px;height: 30px;"
            id="<?php echo $uniqueId ?>">Generate</a></div>

<script type="text/javascript">
$("#<?php echo $uniqueId ?>").on("click", function(evt) {
    evt.preventDefault();
    evt.stopImmediatePropagation();
    $(this).siblings().val((function makeid(len) {
        len = len || 5;
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for( var i=0; i < len; i++ )
            text += possible.charAt(Math.floor(Math.random() * possible.length));

        return text;
    })(20));
});
</script>
