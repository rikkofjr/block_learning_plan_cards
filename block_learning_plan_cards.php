<?php
defined('MOODLE_INTERNAL') || die();

use core_competency\api;
use core_competency\external\competency_exporter;

class block_learning_plan_cards extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_learning_plan_cards');
    }

    public function get_content() {
        global $USER, $PAGE, $OUTPUT, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // 1. Ambil semua rencana belajar yang terhubung dengan user
        $learningplans = $DB->get_records('competency_plan', ['userid' => $USER->id]);
        
        if (empty($learningplans)) {
            $learningplans = api::list_user_plans($USER->id);
        }
        
        if (empty($learningplans)) {
            $this->content->text = html_writer::div(get_string('nocomepetencies', 'block_learning_plan_cards'), 'alert alert-info');
            return $this->content;
        }

        $html = html_writer::start_div('block_learning_plan_cards');

        require_once(__DIR__ . '/../../course/lib.php');

        // 2. LOOPING 1: Iterasi setiap Learning Plan
        foreach ($learningplans as $plan) {
            $planid = is_object($plan) && isset($plan->id) ? $plan->id : (method_exists($plan, 'get') ? $plan->get('id') : 0);
            if ($planid == 0) {
                continue;
            }
            
            $plancompetencies = [];
            try {
                $plancompetencies = api::list_plan_competencies($planid);
            } catch (moodle_exception $e) {
                continue; 
            }

            if (empty($plancompetencies)) {
                continue;
            }

            foreach ($plancompetencies as $pc) {
                if (!isset($pc->competency) || empty($pc->competency)) {
                    continue;
                }
                
                $competency = $pc->competency;

                $competencyid = 0;
                if (isset($competency->id)) {
                    $competencyid = (int)$competency->id;
                } else if (isset($competency->competencyid)) {
                    $competencyid = (int)$competency->competencyid;
                } else if (method_exists($competency, 'get')) {
                    $competencyid = (int)$competency->get('id');
                }

                if ($competencyid == 0) {
                    continue;
                }

                $compdata = $DB->get_record('competency', ['id' => $competencyid], '*', IGNORE_MISSING);
                if (!$compdata) {
                    continue;
                }

                // Section Wadah Utama per Kompetensi
                $html .= html_writer::start_div('competency-section');
                $html .= html_writer::tag('h5', format_string($compdata->shortname) . ' ' . html_writer::tag('small', $compdata->idnumber, ['class' => 'text-muted d-block mt-1']), ['class' => 'mb-3 font-weight-bold']);

                // 3. LOOPING 2: Menarik daftar kursus terhubung melalui kueri SQL langsung
                $linkedcourses = [];
                
                $sql = "SELECT c.* 
                        FROM {course} c
                        JOIN {competency_coursecomp} cc ON cc.courseid = c.id
                        WHERE cc.competencyid = :competencyid";
                
                $dbcourses = $DB->get_records_sql($sql, ['competencyid' => $competencyid]);

                if (!empty($dbcourses)) {
                    foreach ($dbcourses as $course) {
                        $linkedcourses[] = $course;
                    }
                }

                if (empty($linkedcourses)) {
                    $html .= html_writer::div('No course mapped to this competency yet.', 'text-muted small italic pl-2');
                } else {
                    $html .= html_writer::start_div('carousel-horizontal-container');

                    foreach ($linkedcourses as $course) {
                        
                        // Penarikan Gambar Cover Kursus
                        $courseimage = '';
                        $courserenderer = new core_course_list_element($course);
                        $courseimages = $courserenderer->get_course_overviewfiles();
                        
                        if (!empty($courseimages)) {
                            $file = reset($courseimages);
                            $courseimage = moodle_url::make_file_url('/pluginfile.php', '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename())->out(false);
                        } else {
                            $courseimage = $OUTPUT->image_url('monologo', 'theme')->out(); 
                        }

                        $coursecontext = context_course::instance($course->id);

                        // Menghitung Progress Aktivitas Kursus secara Proporsional
                        $progresspercent = 0;
                        $completion = new completion_info($course);
                        
                        if ($completion->is_enabled()) {
                            $ccompletion = new \completion_completion(['userid' => $USER->id, 'course' => $course->id]);
                            if ($ccompletion->is_complete()) {
                                $progresspercent = 100;
                            } else {
                                $modinfo = get_fast_modinfo($course, $USER->id);
                                $totalactivities = 0;
                                $completedactivities = 0;

                                foreach ($modinfo->get_cms() as $cm) {
                                    if (!$cm->uservisible || $cm->completion == COMPLETION_TRACKING_NONE) {
                                        continue;
                                    }
                                    $totalactivities++;
                                    $data = $completion->get_data($cm, true, $USER->id);
                                    if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                                        $completedactivities++;
                                    }
                                }

                                if ($totalactivities > 0) {
                                    $progresspercent = round(($completedactivities / $totalactivities) * 100);
                                }
                            }
                        }

                        // Membuat URL Link Tujuan Kursus Moodle
                        $courselink = new moodle_url('/course/view.php', ['id' => $course->id]);

                        // PERBAIKAN: Membungkus seluruh kontainer Card di dalam tag jangkar tautan <a> agar clickable
                        $cardcontent = html_writer::start_tag('a', [
                            'href' => $courselink->out(), 
                            'class' => 'card course-card-item shadow-sm text-decoration-none',
                            'style' => 'color: inherit; display: flex; flex-direction: column;'
                        ]);
                        
                        // Banner Gambar Atas Kartu
                        $cardcontent .= html_writer::div('', 'course-card-img', ['style' => "background-image: url('$courseimage'); background-color: #f8f9fa;"]);
                        
                        // Badan Informasi Kartu
                        $cardcontent .= html_writer::start_div('card-body p-3 d-flex flex-column justify-content-between');
                        $cardcontent .= html_writer::start_div('card-main-info');
                        $cardcontent .= html_writer::tag('h6', format_string($course->fullname), ['class' => 'text-dark font-weight-bold d-block text-truncate', 'style' => 'max-width: 250px; margin-bottom: 4px;']);
                        
                        // $categoryname = $DB->get_field('course_categories', 'name', ['id' => $course->category], IGNORE_MISSING);
                        // if (!$categoryname) {
                        //     $categoryname = 'Uncategorized';
                        // }
                        // $cardcontent .= html_writer::tag('p', format_string($categoryname), ['class' => 'text-muted small mb-2 text-truncate']);
                        $cardcontent .= html_writer::end_div();

                        // Komponen Progress Bar
                        $cardcontent .= html_writer::start_div('card-progress-section pt-2 border-top');
                        $cardcontent .= html_writer::tag('span', $progresspercent . '% ' , ['class' => 'small text-muted font-weight-bold']);
                        $cardcontent .= html_writer::start_div('progress mt-1', ['style' => 'height: 6px;']);
                        $cardcontent .= html_writer::div('', 'progress-bar bg-success', ['role' => 'progressbar', 'style' => "width: $progresspercent%", 'aria-valuenow' => $progresspercent, 'aria-valuemin' => '0', 'aria-valuemax' => '100']);
                        $cardcontent .= html_writer::end_div();
                        $cardcontent .= html_writer::end_div();

                        $cardcontent .= html_writer::end_div(); // End card-body
                        $cardcontent .= html_writer::end_tag('a'); // End clickable link wrapper

                        $html .= $cardcontent;
                    }

                    $html .= html_writer::end_div(); // End carousel container
                }

                $html .= html_writer::end_div(); // End competency-section
            }
        }

        $html .= html_writer::end_div(); // End block main container

        $this->content->text = $html;
        return $this->content;
    }
}