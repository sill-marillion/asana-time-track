<?php
/**
 * AsanaTimeTracking-Tool
 * - track your time for every single task
 *
 * @author codelovers
 * @author codelovers.de
 * @version 1.0 (2012_07_07)
 * @package asana_track_time
 */

require_once("AsanaApi.php");

// get data
$apiKey = $_GET['apiKey'];
$workspaceId = !empty($_GET['workspaceId']) ? $_GET['workspaceId'] : '';
$projectId = !empty($_GET['projectId']) ? $_GET['projectId'] : '';
$updateId = !empty($_GET['updateId']) ? $_GET['updateId'] : '';

$completionChangeState = !empty($_GET['complete']) ? $_GET['complete'] : '';

// initalize
$asana = new AsanaApi($apiKey); 
$result = $asana->getWorkspaces();

$userId = $asana->getUserId();

// check if everything works fine
if($asana->getResponseCode() == '200' && $result != '' ){

    // ##############################################################################################
    // WORKSPACES
    // ##############################################################################################
    if($workspaceId == '' && $projectId == '' && $updateId == ''){
        
        $resultJson = json_decode($result);
        // $resultJson contains an object in json with all projects
        foreach($resultJson->data as $workspace){
            if($workspace->name == 'Personal Projects') continue;
            echo '<div class="span4 well workspace" data-workspace-id="' . $workspace->id .'"><h3>' . $workspace->name .'</h3></div>';
        }
    }
    
    // ##############################################################################################
    // PROJECTS
    // ##############################################################################################
    
    if($workspaceId != '' && $projectId == ''){
        
        $result = $asana->getProjects($workspaceId);
        
        $resultJson = json_decode($result);
        
        foreach($resultJson->data as $project){
            echo '<div class="span5 well project" data-workspace-id="' . $workspaceId . '" data-project-id="' . $project->id . '"><h3>' . $project->name . '</h3></div>';
        }

    }
    
    // ##############################################################################################
    // TASKS
    // ##############################################################################################
    if($projectId != ''){
        
        //$result = $asana->getTasks($workspaceId);
        $result = $asana->getProjectTasks($projectId);
        $result = json_decode($result);
        $in = false;
        $totalTime = 0;
        
        $completedTasksHtml = '';
        $openTasksHtml = '';
        
        // loop through all Tasks of the result, because the Asana-Api gives us also e.g. the completed ones
        foreach($result->data as $task){
      
             $value = $asana->getEstimatedAndWorkedTime($task->name);
             $taskState = $asana->getOneTask($task->id);

             $taskName = $value['taskName'];
             $estimatedHours = (!empty($value['estimatedHours'])) ? $value['estimatedHours'].'h' : '0h';
             $estimatedMinutes = (!empty($value['estimatedMinutes'])) ? $value['estimatedMinutes'].'m' : '0m';
             $workedHours = (!empty($value['workedHours'])) ? $value['workedHours'].'h' : '0h';
             $workedMinutes = (!empty($value['workedMinutes'])) ? $value['workedMinutes'].'m' : '0m';
             $workedTime = $value['workedTimeSec'];
             
             $totalTime += $workedTime;
             $totalTimeInMinutes = ceil($totalTime / 60000);    
             $totalHours = floor($totalTime / 3600000);
             $totalMinutes = ceil($totalTimeInMinutes % 60);
             
             // progress bar
             $progressBarPercent = ($estimatedHours*60*1000 + $estimatedMinutes * 1000) / 100;
             if($progressBarPercent != 0){
                $progressBarPercent = ($workedHours*60*1000 + $workedMinutes * 1000) / $progressBarPercent;
             }
             
             if($progressBarPercent === '') $progressBarPercent = 100;
             
             $progressState = ($progressBarPercent < 80) ? 'progress-success' : (($progressBarPercent < 100 ) ? 'progress-warning' : 'progress-danger');

             // task must be active and your own
             if($taskState['assignee'] != $userId || $taskName == '') {
                continue;
             } else {
                 
                if($taskState['completed']) {
                    $completedTasksHtml .= '<tr class="completed-task">'
                    .'<td class="task-caption">'. $taskName  .'</td>'
                    .'<td class="estimated_time" data-estimated-hours="'.$value['estimatedHours'].'" data-estimated-minutes="'.$value['estimatedMinutes'].'">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $estimatedHours .' '. $estimatedMinutes . '</span>'
                        . '<input class="date-picker-et" name="date-picker-et"/>'
                    . '</td>'
                    .'<td class="worked_time" data-worked-hours="'.$value['workedHours'].'" data-worked-minutes="'.$value['workedMinutes'].'" data-task-id="' . $task->id . '" data-task-name="' . $taskName . '">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $workedHours .' '. $workedMinutes . '</span>'
                        . '<input class="date-picker-wt" name="date-picker-wt"/>'
                    . '</td>'
                    .'<td class="my_progress"><div class="progress ' . $progressState . ' progress-striped">
                            <div class="bar" style="width: ' . $progressBarPercent . '%;"></div>
                        </div>
                      </td>'
                    .'<td class="my_timer">
                        <div class="time">00:00:00</div>
                        <button class="btn btn-success" type="submit">
                            <i class="icon-white icon-play"></i><span class="start_stop_text">Start</span>
                        </button>
                      </td>'
                    .'<td class="completion-box form-checkbox"><form><input type="checkbox" name="completed" value="is_completed" class="complete-checkbox closed"  data-task-id="' . $task->id . '" checked></form></td>' 
                    .'</tr>';
                }  else {
                    $openTasksHtml .= '<tr class="open-task">'
                    .'<td class="task-caption">'. $taskName  .'</td>'
                    .'<td class="estimated_time" data-estimated-hours="'.$value['estimatedHours'].'" data-estimated-minutes="'.$value['estimatedMinutes'].'">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $estimatedHours .' '. $estimatedMinutes . '</span>'
                        . '<input class="date-picker-et" name="date-picker-et"/>'
                    . '</td>'
                    .'<td class="worked_time" data-worked-hours="'.$value['workedHours'].'" data-worked-minutes="'.$value['workedMinutes'].'" data-task-id="' . $task->id . '" data-task-name="' . $taskName . '">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $workedHours .' '. $workedMinutes . '</span>'
                        . '<input class="date-picker-wt" name="date-picker-wt"/>'
                    . '</td>'
                    .'<td class="my_progress"><div class="progress ' . $progressState . ' progress-striped">
                            <div class="bar" style="width: ' . $progressBarPercent . '%;"></div>
                        </div>
                      </td>'
                    .'<td class="my_timer">
                        <div class="time">00:00:00</div>
                        <button class="btn btn-success" type="submit">
                            <i class="icon-white icon-play"></i><span class="start_stop_text">Start</span>
                        </button>
                      </td>'
                    .'<td class="completion-box form-checkbox"><form><input type="checkbox" name="completed" value="is_completed" class="complete-checkbox open" data-task-id="' . $task->id . '"></form></td>'
                    .'</tr>';
                }
                 
              /*  echo ($taskState['completed']) ? '<tr class="completed-task">' : '<tr>';
                echo '<td>'. $taskState['projects']['name'] .'</td>'
                    .'<td>'. $taskName  .'</td>'
                    .'<td class="estimated_time" data-estimated-hours="'.$value['estimatedHours'].'" data-estimated-minutes="'.$value['estimatedMinutes'].'">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $estimatedHours .' '. $estimatedMinutes . '</span>'
                        . '<input class="date-picker-et" name="date-picker-et"/>'
                    . '</td>'
                    .'<td class="worked_time" data-worked-hours="'.$value['workedHours'].'" data-worked-minutes="'.$value['workedMinutes'].'" data-task-id="' . $task->id . '" data-task-name="' . $taskName . '">'
                        . '<span class="my_label" rel="tooltip" title="click to edit">' . $workedHours .' '. $workedMinutes . '</span>'
                        . '<input class="date-picker-wt" name="date-picker-wt"/>'
                    . '</td>'
                    .'<td class="my_progress"><div class="progress ' . $progressState . ' progress-striped">
                            <div class="bar" style="width: ' . $progressBarPercent . '%;"></div>
                        </div>
                      </td>'
                    .'<td class="my_timer">
                        <div class="time">00:00:00</div>
                        <button class="btn btn-success" type="submit">
                            <i class="icon-white icon-play"></i><span class="start_stop_text">Start</span>
                        </button>
                      </td>';
                    echo '<td><form><input type="checkbox" name="completed" value="is_completed" ' . (($taskState['completed']) ? 'checked' : '' ) . '></form></td>';  
                    echo '</tr>';*/
               
                    
                // at least one assigend task is found
                $in = true;    
             }
         }

        echo $completedTasksHtml;
        echo $openTasksHtml;

         // no assigned task is found
         if(!$in) echo '<tr><td colspan="6">Sorry, no assigned tasks are found...</td></tr>';
         
         echo '<tr class="worked_time_line"><td colspan="3"><td class="text_align right">Worked today:</td><td class="worked_time_today">0 hours 0 minutes</td><td colspan="1"></tr>';
         echo '<tr class="worked_time_line"><td colspan="3"><td class="text_align right">Work total:</td><td class="worked_time_total" hr="' . $totalHours . '" min="' . $totalMinutes . '">' . $totalHours . ' hours ' . $totalMinutes . ' minutes</td><td colspan="1"></tr>';   
        
    }
    
    // ##############################################################################################
    // UPDATE
    // ##############################################################################################
    if($updateId != ''){
    
        if($completionChangeState == '') {
            $workedHours = $_GET['workedHours'];
            $workedMinutes = $_GET['workedMinutes'];
            $estimatedHours = $_GET['estimatedHours'];
            $estimatedMinutes = $_GET['estimatedMinutes'];
            $currentTaskName = $_GET['taskName'];
            
            $asana->updateTask($updateId, $workedHours, $workedMinutes, $estimatedHours, $estimatedMinutes,  $currentTaskName);
        } else {
            $asana->updateTaskCompletionState($updateId, $completionChangeState);
            echo $completionChangeState;
        }
    }
    

} else {
    echo '<p>ERROR: Something went wrong! Maybe your asana api key does not fit.<br/> Or you have no internet connection.</p>';
}