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

<script type="text/javascript">
    // FIXME create custom event using plain javascript only!
    (function() {
        "use strict";

        var $obj = $("[name=<?php echo $self['name'] ?>]");
        var criterias = JSON.parse('<?php echo json_encode($criteria) ?>');
        var baseUrl = "<?php echo URL::site('/'.$foreign->name). '.json' ?>";
        var foreignKey = "<?php echo $self['foreignKey'] ?>";
        var foreignLabel = "<?php echo $self['foreignLabel'] ?>";

        $(function() {
            $obj.trigger('change');
        });

        if (criteria) {
            for(var k in criteria) {
                var v = criteria[k];

                $('[name=' + v + ']').on('change', function() {
                    var crits = {},
                        $this = $(this);

                    for(var i in criteria) {
                        crits[i] = $('[name=' + criteria[i] + ']').val();
                    }

                    $.get(baseUrl + '?' + $.param(crits)).done(function(data) {
                        $obj.html('<option value="">---</option>');
                        if (data && data.entries) {
                            for(var i in data.entries) {
                                var entry = data.entries[i],
                                    val = entry[foreignKey];

                                $obj.append('<option value="' + val + '" ' + ($obj.attr('data-value') == val ? 'selected' : '') + '>' + entry[foreignLabel] + '</option>');
                            }
                        }
                    });
                });
            }
        }

    })();
</script>