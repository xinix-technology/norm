<?php $uniqId = uniqid('nfile-') ?>
<div id="<?php echo $uniqId ?>" class="field-group nfile">
    <input type="file" class="file" style="display:none!important">
    <a href="#" class="button">Choose file</a>
    <span class="viewer">
        <?php echo $value ?>
        <input type="hidden" name="<?php echo $self["name"] ?>" value="<?php echo $value ?>">
    </span>
</div>

<script>
(function () {
    'use strict';

    var uploadUrl = "<?php echo $self->getAttribute('nfile.uploadUrl') ?>";

    var containerEl = document.getElementById('<?php echo $uniqId ?>');
    var fileEl = containerEl.querySelector('.file');
    var buttonEl = containerEl.querySelector('.button');
    var viewerEl = containerEl.querySelector('.viewer');

    buttonEl.addEventListener('click', function (evt) {
        evt.preventDefault();
        fileEl.click();
    });

    fileEl.addEventListener('change', function () {
        var files = this.files;

        function undo() {
            for (var i = 0; i < files.length; i++) {
                files[i].$el.innerHTML = '';
            }
        }

        new Promise(function (resolve, reject) {
            var form = new FormData();
            for (var i = 0; i < files.length; i++) {
                var file = files[i];

                var progressEl = document.createElement('progress');
                progressEl.max = 100;
                viewerEl.appendChild(progressEl);
                file.$progressEl = progressEl;

                viewerEl.appendChild(document.createTextNode(' ' + file.name));

                var inputEl = document.createElement('input');
                inputEl.type = 'hidden';
                inputEl.name = '<?php echo $self["name"] ?>';
                inputEl.value = file.name;
                viewerEl.appendChild(inputEl);
                file.$el = viewerEl;

                // Add the file to the request.
                form.append('files[]', file); //, file.name);
            }

            var request = new XMLHttpRequest();
            request.onprogress = function (evt) {
                if ((evt.totalSize || evt.total) > 0) {
                    var percentComplete = ((evt.position || evt.loaded) / (evt.totalSize || evt.total))*100;
                    for (var i = 0; i < files.length; i++) {
                        files[i].$progressEl.value = percentComplete;
                    }
                }
            };

            request.onerror = undo;

            request.onload = function (evt) {
                if (request.status >= 200 && request.status < 400) {
                    var data = JSON.parse(request.responseText);
                    if (data.files.length !== files.length) {
                        undo();
                        return;
                    }
                    for (var i = 0; i < files.length; i++) {
                        files[i].$progressEl.value = 100;
                        form.append('files[]', files[i]); //, file.name);
                    }
                    // Success!
                } else {
                    undo();
                }
            };
            request.open('POST', uploadUrl, true);
            request.setRequestHeader('X-Data-Dir', '<?php echo $self->getDataDir() ?>');
            request.send(form);
        });
    });
})();
</script>
