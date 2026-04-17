<?php
/**
 * Lab Trial Reports - Create/Edit (Basic Metadata Only)
 */

$report_id = intval($_GET['id'] ?? 0);
$report = null;

if ($report_id > 0) {
    $qry = $conn->query("SELECT * FROM lab_trial_reports WHERE id = $report_id LIMIT 1");
    if ($qry && $qry->num_rows > 0) {
        $report = $qry->fetch_assoc();
    }
}

$client_options = [];
$clients_qry = $conn->query("SELECT id, company_name FROM clients ORDER BY company_name ASC");
if ($clients_qry) {
    while ($c = $clients_qry->fetch_assoc()) {
        $client_options[] = $c;
    }
}

$report_name_value = $report['name'] ?? ($report['product_name'] ?? '');
$batch_no_value = $report['batch_no'] ?? '';
$trial_no_value = $report['trial_no'] ?? '';
$batch_size_value = $report['batch_size'] ?? '';
$company_value = $report['company'] ?? 'Hugopharm';
$client_id_value = isset($report['client_id']) ? intval($report['client_id']) : 0;
$trial_date_range_value = $report['trial_date_range'] ?? '';
$client_representative_value = $report['client_representative'] ?? '';
$objective_value = $report['objective'] ?? '';
$equipment_value = $report['equipment'] ?? '';
$sections = [
    'purpose' => '',
    'input_characteristics' => '',
    'formula' => '',
    'observations' => '',
    'results_evaluation' => '',
    'future_action' => ''
];

if ($report) {
    foreach ($sections as $key => $value) {
        if (isset($report[$key]) && $report[$key] !== null && $report[$key] !== '') {
            $sections[$key] = $report[$key];
        }
    }

    // Backward compatibility for older rows where section content was stored in description as JSON.
    if (!empty($report['description'])) {
        $decoded = json_decode($report['description'], true);
        if (is_array($decoded)) {
            foreach ($sections as $key => $value) {
                if (empty($sections[$key]) && isset($decoded[$key])) {
                    $sections[$key] = $decoded[$key];
                }
            }
        } else if (empty($sections['purpose'])) {
            $sections['purpose'] = $report['description'];
        }
    }
}
?>

<style>
    .section-toggle-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        text-align: left;
        font-weight: 600;
        padding: 10px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #f8f9fa;
    }
    .section-toggle-btn:focus,
    .section-toggle-btn:active,
    .section-toggle-btn:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }
    .section-toggle-btn .icon {
        transition: transform 0.2s ease;
    }
    .section-toggle-btn[aria-expanded="true"] .icon {
        transform: rotate(180deg);
    }
    .section-collapse {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 12px;
        overflow-anchor: none;
    }
    .section-collapse.collapsing {
        transition: height 0.22s ease-in-out;
        will-change: height;
    }
    .daterangepicker.single-calendar-range .drp-calendar.right {
        display: none !important;
    }
    .daterangepicker.single-calendar-range .drp-calendar.left {
        float: none !important;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><?php echo $report ? 'Edit Lab Trial Report' : 'Create New Lab Trial Report'; ?></h3>
                <div class="card-tools">
                    <a href="<?php echo base_url ?>admin/?page=lab_trial_reports" class="btn btn-sm btn-secondary">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div id="trial-sections-accordion" class="mt-2">
                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_trial_details" aria-expanded="false" aria-controls="sec_trial_details">
                            <span>Trial Details</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_trial_details" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label>Product Name <span class="text-danger">*</span></label>
                                    <input type="text" id="report_name" class="form-control" value="<?php echo htmlspecialchars($report_name_value); ?>" placeholder="Enter product name">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Batch No</label>
                                    <input type="text" id="batch_no" class="form-control" value="<?php echo htmlspecialchars($batch_no_value); ?>" placeholder="Enter batch no">
                                </div>
                                <div class="col-md-4">
                                    <label>Trial No</label>
                                    <input type="text" id="trial_no" class="form-control" value="<?php echo htmlspecialchars($trial_no_value); ?>" placeholder="Enter trial no">
                                </div>
                                <div class="col-md-4">
                                    <label>Batch Size</label>
                                    <input type="text" id="batch_size" class="form-control" value="<?php echo htmlspecialchars($batch_size_value); ?>" placeholder="Enter batch size">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Company</label>
                                    <select id="trial_company" class="form-control select2">
                                        <option value="Hugopharm" <?php echo ($company_value === 'Hugopharm') ? 'selected' : ''; ?>>Hugopharm</option>
                                        <option value="S.B. Panchal" <?php echo ($company_value === 'S.B. Panchal') ? 'selected' : ''; ?>>S.B. Panchal</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Client</label>
                                    <select id="client_id" class="form-control select2">
                                        <option value="">Select Client</option>
                                        <?php foreach ($client_options as $client_row): ?>
                                            <option value="<?php echo intval($client_row['id']); ?>" <?php echo ($client_id_value === intval($client_row['id'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client_row['company_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Date of Trial</label>
                                    <input type="text" id="trial_date_range" class="form-control" value="<?php echo htmlspecialchars($trial_date_range_value); ?>" placeholder="Select trial date range">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Clients Representative</label>
                                    <input type="text" id="client_representative" class="form-control" value="<?php echo htmlspecialchars($client_representative_value); ?>" placeholder="Enter representative name">
                                </div>
                                <div class="col-md-6">
                                    <label>Equipment</label>
                                    <input type="text" id="equipment" class="form-control" value="<?php echo htmlspecialchars($equipment_value); ?>" placeholder="Enter equipment details">
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-md-12">
                                    <label>Objective</label>
                                    <textarea id="objective" class="form-control" rows="2" placeholder="Enter objective"><?php echo htmlspecialchars($objective_value); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_purpose" aria-expanded="false" aria-controls="sec_purpose">
                            <span>Purpose</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_purpose" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="purpose" class="form-control trial-editor" rows="6"><?php echo $sections['purpose']; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_input_characteristics" aria-expanded="false" aria-controls="sec_input_characteristics">
                            <span>Input Characteristics</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_input_characteristics" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="input_characteristics" class="form-control trial-editor" rows="6"><?php echo $sections['input_characteristics']; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_formula" aria-expanded="false" aria-controls="sec_formula">
                            <span>Formula</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_formula" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="formula" class="form-control trial-editor" rows="6"><?php echo $sections['formula']; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_observations" aria-expanded="false" aria-controls="sec_observations">
                            <span>Observations</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_observations" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="observations" class="form-control trial-editor" rows="6"><?php echo $sections['observations']; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_results_evaluation" aria-expanded="false" aria-controls="sec_results_evaluation">
                            <span>Results/Evaluation</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_results_evaluation" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="results_evaluation" class="form-control trial-editor" rows="6"><?php echo $sections['results_evaluation']; ?></textarea>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button class="section-toggle-btn" type="button" data-toggle="collapse" data-target="#sec_future_action" aria-expanded="false" aria-controls="sec_future_action">
                            <span>Future Action</span><span class="icon"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div id="sec_future_action" class="collapse section-collapse" data-parent="#trial-sections-accordion">
                            <textarea id="future_action" class="form-control trial-editor" rows="6"><?php echo $sections['future_action']; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button id="ltr_save_report" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script>
;(function(){
    var base = typeof _base_url_ !== 'undefined' ? _base_url_ : '<?php echo base_url ?>';
    var reportId = <?php echo $report_id; ?>;

    function isSummernoteReady(id){
        return $('#' + id).next('.note-editor').length > 0;
    }

    function initEditor(id){
        if(isSummernoteReady(id)) return;
        $('#' + id).summernote({
            height: 260,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    function getEditorContent(id){
        if(isSummernoteReady(id)) return $('#' + id).summernote('code');
        return $('#' + id).val() || '';
    }

    function saveReport(){
        var name = $('#report_name').val().trim();
        var batchNo = $('#batch_no').val().trim();
        var trialNo = $('#trial_no').val().trim();
        var batchSize = $('#batch_size').val().trim();
        var company = $('#trial_company').val();
        var clientId = $('#client_id').val();
        var trialDateRange = $('#trial_date_range').val().trim();
        var clientRepresentative = $('#client_representative').val().trim();
        var objective = $('#objective').val().trim();
        var equipment = $('#equipment').val().trim();
        var payload = {
            purpose: getEditorContent('purpose'),
            input_characteristics: getEditorContent('input_characteristics'),
            formula: getEditorContent('formula'),
            observations: getEditorContent('observations'),
            results_evaluation: getEditorContent('results_evaluation'),
            future_action: getEditorContent('future_action')
        };

        if(!name){
            Swal.fire({icon:'warning', title:'Required', text:'Please enter a report name'});
            return;
        }

        var btn = $('#ltr_save_report');
        btn.prop('disabled', true);

        $.ajax({
            url: base + 'classes/Master.php?f=save_lab_trial_report',
            method: 'POST',
            data: {
                id: reportId,
                name: name,
                sections_json: JSON.stringify(payload),
                purpose: payload.purpose,
                input_characteristics: payload.input_characteristics,
                formula: payload.formula,
                observations: payload.observations,
                results_evaluation: payload.results_evaluation,
                future_action: payload.future_action,
                batch_no: batchNo,
                trial_no: trialNo,
                batch_size: batchSize,
                company: company,
                client_id: clientId,
                trial_date_range: trialDateRange,
                client_representative: clientRepresentative,
                objective: objective,
                equipment: equipment
            },
            dataType: 'json',
            success: function(resp){
                btn.prop('disabled', false);
                if(resp.status === 'success'){
                    reportId = resp.report_id;
                    window.location.href = base + 'admin/?page=lab_trial_reports/view_trial&id=' + encodeURIComponent(reportId) + '&success=true';
                } else {
                    Swal.fire({icon:'error', title:'Error', text: resp.msg || 'Save failed'});
                }
            },
            error: function(){
                btn.prop('disabled', false);
                Swal.fire({icon:'error', title:'Error', text:'Save request failed'});
            }
        });
    }

    $(function(){
        if ($.fn.select2) {
            $('#trial_company').select2({ width: '100%' });
            $('#client_id').select2({ width: '100%' });
        }

        if ($.fn.daterangepicker) {
            $('#trial_date_range').daterangepicker({
                autoUpdateInput: $('#trial_date_range').val().trim() !== '',
                linkedCalendars: false,
                autoApply: true,
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: ' to '
                }
            });
            $('#trial_date_range').on('show.daterangepicker', function(ev, picker) {
                picker.container.addClass('single-calendar-range');
            });
            $('#trial_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format('DD-MM-YYYY'));
            });
        }

        // Initialize target editor before Bootstrap computes collapse height to reduce animation jitter.
        $(document).on('click', '.section-toggle-btn', function(){
            var target = $(this).attr('data-target');
            if(!target) return;
            var targetEditor = $(target).find('textarea.trial-editor').attr('id');
            if(targetEditor){
                initEditor(targetEditor);
            }
        });

        $('#ltr_save_report').on('click', function(e){
            e.preventDefault();
            saveReport();
        });
    });
})();
</script>
