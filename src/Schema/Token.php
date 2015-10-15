<?php

namespace Norm\Schema;

class Token extends String
{
    public function formatInput($value, $entry = null)
    {
        $uniqueId = uniqid($this['name'].'_');
        // FIXME should be overriden for UX
        return '<div style="display: flex"><input type="text" name="'.$this['name'].'" value="'.$value.
            '" placeholder="'.l($this['label']).'" autocomplete="off" /><a href="#"
            style="display: block;padding-right: 15px;line-height: 30px;height: 30px;" id="'.$uniqueId.'"
            >Generate</a></div>
            <script type="text/javascript">
            $("#'.$uniqueId.'").on("click", function(evt) {
                evt.preventDefault();
                evt.stopImmediatePropagation();
                console.log("xxxx");
                $(this).siblings().val((function makeid(len)
{
    len = len || 5;
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < len; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
})(20));
            });
            </script>
            ';
    }
}
