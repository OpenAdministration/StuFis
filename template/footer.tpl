				</div> <!-- close container -->

<!-- Modal -->
<div class="modal fade" id="please-wait-dlg" tabindex="-1" role="dialog" aria-labelledby="please-wait-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="please-wait-label">Bitte warten</h4>
      </div>
      <div class="modal-body">
        Bitte warten, die Anfrage wird verarbeitet.
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="server-message-dlg" tabindex="-1" role="dialog" aria-labelledby="server-message-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="server-message-label">Antwort vom Server</h4>
      </div>
      <div class="modal-body" id="server-message-content">
        Und die Lösung lautet..
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="server-question-dlg" tabindex="-1" role="dialog" aria-labelledby="server-question-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="server-question-label">Antwort vom Server</h4>
      </div>
      <div class="modal-body" id="server-question-content">
        Und die Lösung lautet..
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
        <button type="button" class="btn btn-primary" id="server-question-close-window">Fenster schließen</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="rename-file-dlg" tabindex="-1" role="dialog" aria-labelledby="rename-file-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="rename-file-label">Datei umbenennen</h4>
      </div>
      <div class="modal-body" id="rename-file-content">
        <div class="form-group">
          <label for="rename-file-oldname">Ursprünglicher Name</label>
          <input type="text" class="form-control" id="rename-file-oldname" readonly="readonly">
        </div>
        <div class="form-group">
          <label for="rename-file-newname">Neuer Name</label>
          <input type="text" class="form-control" id="rename-file-newname">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbruch</button>
        <button type="button" class="btn btn-primary" id="rename-file-ok">Datei umbenennen</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="delete-file-dlg" tabindex="-1" role="dialog" aria-labelledby="delete-file-label">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="delete-file-label">Datei löschen</h4>
      </div>
      <div class="modal-body" id="delete-file-content">
        <div class="form-group">
          <label for="delete-file-name">Name</label>
          <input type="text" class="form-control" id="delete-file-name" readonly="readonly">
        </div>
        <div class="form-group">
          <label for="delete-file-size">Größe</label>
          <input type="text" class="form-control" id="delete-file-size" readonly="readonly">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbruch</button>
        <button type="button" class="btn btn-primary" id="delete-file-ok">Datei löschen</button>
      </div>
    </div>
  </div>
</div>

		</body>
</html>
