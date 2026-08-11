@php
    $selectedAssignees = collect(old('assignees', isset($task) && $task->exists ? $task->assignees->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
    $dueAtValue = old('due_at');
    if (!$dueAtValue && !empty($task->due_at)) {
        $dueAtValue = $task->due_at->format('Y-m-d\TH:i');
    }
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $task->exists ? 'Modifier la tâche' : 'Nouvelle tâche' }}</strong>
        <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-secondary">Retour</a>
    </div>
    <div class="card-body">
        <div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3" id="taskVoicePanel">
            <div>
                <strong>Dictée vocale</strong>
                <span class="text-muted small ms-2" id="taskVoiceStatus">Prête</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select class="form-control form-control-sm" id="taskVoiceLanguage" style="width: 150px;">
                    <option value="fr-FR">Français</option>
                    <option value="tr-TR">Türkçe</option>
                    <option value="en-US">English</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Titre</label>
                <div class="input-group">
                    <input type="text" id="taskTitleInput" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $task->title) }}" required maxlength="180">
                    <button type="button" class="btn btn-outline-secondary task-voice-button" data-target="taskTitleInput" data-mode="replace" title="Dicter le titre">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $task->status ?: 'open') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Priorité</label>
                <select name="priority" class="form-control @error('priority') is-invalid @enderror">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $task->priority ?: 'normal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Description</label>
                <div class="input-group">
                    <textarea id="taskDescriptionInput" name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description', $task->description) }}</textarea>
                    <button type="button" class="btn btn-outline-secondary task-voice-button" data-target="taskDescriptionInput" data-mode="append" title="Dicter la description">
                        <i class="fas fa-microphone"></i>
                    </button>
                </div>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Échéance</label>
                <input type="datetime-local" name="due_at" class="form-control @error('due_at') is-invalid @enderror" value="{{ $dueAtValue }}">
                @error('due_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label">Assignée à</label>
                <select name="assignees[]" class="form-control @error('assignees') is-invalid @enderror" multiple size="6">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(in_array((int) $user->id, $selectedAssignees, true))>
                            {{ $user->name }} @if($user->email) - {{ $user->email }} @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Maintenez Cmd/Ctrl pour sélectionner plusieurs utilisateurs.</small>
                @error('assignees')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="border rounded p-3 bg-light mb-3">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Lien avec un dossier</label>
                    <select name="taskable_type" class="form-control @error('taskable_type') is-invalid @enderror">
                        @foreach($relatedTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('taskable_type', $task->taskable_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('taskable_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">ID</label>
                    <input type="number" min="1" name="taskable_id" class="form-control @error('taskable_id') is-invalid @enderror" value="{{ old('taskable_id', $task->taskable_id) }}">
                    @error('taskable_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Libellé du lien</label>
                    <input type="text" name="taskable_label" class="form-control @error('taskable_label') is-invalid @enderror" value="{{ old('taskable_label', $task->taskable_label) }}" maxlength="255" placeholder="Ex: Dossier 10212, Demande 438, véhicule TEMSA...">
                    @error('taskable_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const buttons = document.querySelectorAll('.task-voice-button');
    const status = document.getElementById('taskVoiceStatus');
    const language = document.getElementById('taskVoiceLanguage');
    let recognition = null;
    let activeButton = null;

    function setStatus(message, className = 'text-muted') {
        if (!status) return;
        status.className = className + ' small ms-2';
        status.textContent = message;
    }

    if (!SpeechRecognition) {
        buttons.forEach(button => button.disabled = true);
        setStatus('Dictée non supportée par ce navigateur. Utilisez Chrome.', 'text-danger');
        return;
    }

    function stopListening() {
        if (recognition) {
            recognition.stop();
        }
    }

    buttons.forEach(button => {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.dataset.target);
            if (!target) return;

            if (activeButton === button && recognition) {
                stopListening();
                return;
            }

            if (recognition) {
                recognition.abort();
            }

            recognition = new SpeechRecognition();
            recognition.lang = language?.value || 'fr-FR';
            recognition.interimResults = true;
            recognition.continuous = false;
            activeButton = button;
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-danger');
            setStatus('Écoute en cours...', 'text-danger');

            const originalValue = target.value;

            function writeVoiceText(text) {
                const cleanText = text.trim();
                if (!cleanText) return;

                if (button.dataset.mode === 'append') {
                    const base = originalValue.replace(/\s+$/, '');
                    const separator = base ? "\n" : '';
                    target.value = base + separator + cleanText;
                    return;
                }

                target.value = cleanText.slice(0, Number(target.getAttribute('maxlength') || 10000));
            }

            recognition.onresult = function (event) {
                let finalText = '';
                let interimText = '';

                for (let i = 0; i < event.results.length; i++) {
                    const text = event.results[i][0].transcript.trim();
                    if (event.results[i].isFinal) {
                        finalText += (finalText ? ' ' : '') + text;
                    } else {
                        interimText += (interimText ? ' ' : '') + text;
                    }
                }

                writeVoiceText((finalText + ' ' + interimText).trim());
            };

            recognition.onerror = function (event) {
                setStatus(event.error === 'not-allowed' ? 'Micro refusé par le navigateur.' : 'Erreur dictée: ' + event.error, 'text-danger');
            };

            recognition.onend = function () {
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-secondary');
                activeButton = null;
                recognition = null;
                if (status?.textContent === 'Écoute en cours...') {
                    setStatus('Dictée terminée', 'text-success');
                }
            };

            recognition.start();
        });
    });
});
</script>
