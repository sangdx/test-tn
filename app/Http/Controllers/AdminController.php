<?php

namespace App\Http\Controllers;

use Log;
use Excel;
use Input;
use Illuminate\Http\Request;
use Illuminate\Http\Concerns\hasFile;
use App\Exam;
use Illuminate\Support\Facades\Session;
use App\Consts;
use App\Subject;
use App\User;
use DB;
use App\ExamResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function __construct()
    {
    }

    public function layoutUploadExam()
    {
        return view('manage.upload_exam');
    }

    public function import(Request $request)
    {
        if($request->hasFile('import'))
        {
            $action = $request['action'];
            $subjectId = $this->getSubjectID($action);
            $examId = $this->getExamID($action);
            $class = $request['class'];
            $path = $request->file('import')->getRealPath();
            $data = Excel::load($path, function($reader) {
            })->get();
            if(!empty($data) && $data->count()){
                $data = $data->toArray();
                for($i=0;$i<count($data);$i++) {
                    unset($data[$i]['0']);
                    $data[$i]['subject_id'] = $subjectId;
                    $data[$i]['exam_id'] = $examId;
                    $data[$i]['class'] = $class;
                    $dataImported[] = $data[$i];
                }
            }
            Exam::insert($dataImported);
            Session::put('success', 'Upload exam success');
            return redirect()->route('list-exam');
        } else {
            return back();
        }

    }

    public function getSubjectID($action)
    {
        switch ($action) {
            case Consts::MATH:
                $name = 'Toán';
                break;
            case Consts::MATH_1:
                $name = 'Toán1';
                break;
            case Consts::LY:
                $name = 'Lý';
                break;
            case Consts::HOA:
                $name = 'Hóa';
                break;
            default:
                $name = 'Anh';
                break;
        }
        return $this->getSubjectByName($name);
    }

    public function getExamID($action)
    {
        switch ($action) {
            case Consts::MATH:
                $prifix = 'T';
                break;
            case Consts::MATH_1:
                $prifix = 'M';
                break;
            case Consts::LY:
                $prifix = 'L';
                break;
            case Consts::HOA:
                $prifix = 'H';
                break;
            default:
                $prifix = 'A';
                break;
        }
        return $prifix . rand(1,9) . rand(0,9) . rand(0,9) . rand(0,9);
    }

    public function ShowExam()
    {
        $exams = Exam::selectRaw('exams.exam_id, subjects.name, subjects.time_test, exams.class as class,' . DB::raw('COUNT(exam_id)') . 'as num_exam')
                        ->join('subjects', 'subjects.id', '=', 'exams.subject_id')
                        ->groupBy('exams.exam_id')
                        ->groupBy('subjects.name')
                        ->groupBy('subjects.time_test')
                        ->groupBy('exams.class')
                        ->paginate(Consts::LIMIT);
        return view('manage.exam_list', ['exams' => $exams]);
    }
    public function getSubjectByName($name)
    {
        $subject = Subject::where('name', $name)->first();
        return $subject->id;
    }
    public function uploadResult(Request $request)
    {
        if($request->hasFile('import'))
        {
            $subjectId = $this->getSubjectByName($request['subject_name']);
            $examId = $request['exam_id'];
            $class = $request['class'];
            $path = $request->file('import')->getRealPath();
            $data = Excel::load($path, function($reader) {
            })->get();
            if(!empty($data) && $data->count()){
                $data = $data->toArray();
                for($i=0;$i<count($data);$i++) {
                    unset($data[$i]['0']);
                    $data[$i]['subject_id'] = $subjectId;
                    $data[$i]['exam_id'] = $examId;
                    $data[$i]['class'] = $class;
                    $dataImported[] = $data[$i];
                }
            }
            ExamResult::insert($dataImported);
            Session::put('success', 'Upload result success');
        }
        return redirect()->route('list-exam');
    }

    public function ShowResult()
    {
        $results = ExamResult::selectRaw('exam_results.exam_id, subjects.name, subjects.time_test,' . DB::raw('COUNT(exam_id)') . 'as num_exam')
                        ->join('subjects', 'subjects.id', '=', 'exam_results.subject_id')
                        ->groupBy('exam_results.exam_id')
                        ->groupBy('subjects.name')
                        ->groupBy('subjects.time_test')
                        ->paginate(Consts::LIMIT);
        return view('manage.result_list', ['results' => $results]);
    }

    public function showUser()
    {
        $users = User::where('is_admin', '!=', User::TYPE_ADMIN)
                    ->paginate(Consts::LIMIT);
        return view('manage.list_user', ['users' => $users]);
    }
    public function showUploadImg()
    {
        return view('manage.upload_img');
    }

    public function uploadImg(Request $request)
    {
        $file = $request['file'];
        if (is_file($file)) {
            $res = [];
            $fileName = str_replace(".", "", microtime(true)) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fileName, File::get($file));
            $res["path"] = Storage::disk('public')->url($fileName);
            return view('manage.upload_img_result', ['res' => $res]);
        }
        return back();
    }
}
