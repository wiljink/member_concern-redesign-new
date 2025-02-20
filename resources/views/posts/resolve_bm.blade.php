<x-app-layout>

    <!-- Rest of the Page Content -->
    <h1 class="text-4xl font-bold text-center">Resolved Concern</h1>
    <div align='center'>
        @if(session()->has('success'))
        <div class="alert alert-success">
            {!! session('success') !!}
        </div>
        @endif
    </div>

    <div class="w-3/4 mx-auto">

    <table class="table w-full mt-4">
            <thead>
                <tr>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">ID</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">NAME</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">BRANCH</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">CONTACT NUMBER</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">CONCERN RECEIVED DATE</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">CONCERN</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">MESSAGE</th>
                    @if($authenticatedUser['account_type_id'] == 7)
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">ENDORSED BY</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">TASK</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">DAYS RESOLVED</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">DATE RESOLVED</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">RESOLVED BY</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">STATUS</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">MEMBER FEEDBACK</th>
                    @endif
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">MEMBER ASSESSMENT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $posts)
                <tr>
                    <td>{{ $posts->id }}</td>
                    <td>{{ $posts->name }}</td>
                    <td>
                        @foreach($branches as $branch)
                            @if($posts->branch == $branch['id'])
                                {{ $branch['branch_name'] }}
                            @endif
                        @endforeach
                    </td>
                    <td>{{ $posts->contact_number }}</td>
                    <td>{{ $posts->created_at->format('Y-m-d') }}</td>
                    <td>{{ $posts->concern }}</td>
                    <td>{{ $posts->message }}</td>
                    @if($authenticatedUser['account_type_id'] == 7)
                    @php
                    $token = session('token');
                    $endorse_by = $posts->endorse_by;
                    $api_link = "https://loantracker.oicapp.com/api/v1/users/" . $endorse_by;
                    $response3 = Http::withToken($token)->get($api_link);
                    $user = $response3->json();
                
                    @endphp
                    <td>{{ $user['user']['officer']['fullname'] }}</td>
                    <td class="expanded-column">
                        @php
                        $tasks = json_decode($posts->tasks, true);
                        @endphp
                        @if($tasks && is_array($tasks) && count($tasks) > 0)
                        <ol style="font-family: 'Poppins', sans-serif;">
                            @foreach($tasks as $task)
                            <li>{{ $task }}</li>
                            @endforeach
                        </ol>
                        @else
                        <p style="color: red;">No tasks available.</p>
                        @endif
                    </td>
                    <td>@if ($posts->resolved_days)
                        @php
                            $resolvedDays = json_decode($posts->resolved_days, true);
                        @endphp
                        {{ $resolvedDays['days'] ?? 'N/A' }} days, 
                        {{ $resolvedDays['hours'] ?? 'N/A' }} hours, 
                        {{ $resolvedDays['minutes'] ?? 'N/A' }} minutes
                    @else
                        N/A
                    @endif</td>
                    <td>{{ $posts->resolved_date ?? 'N/A' }}</td>
                    <td>{{ $posts->resolve_by ?? 'N/A' }}</td>
                    <td>{{ $posts->status ?? 'Pending' }}</td>
                    <td>{{ $posts->member_comments ?? 'N/A' }}</td>
                    <td>{{ $posts->assess ?? 'N/A' }}</td>
                    @endif
                    <td>
                        @if($authenticatedUser['account_type_id'] == 7)
                        @if($posts->status !== 'Resolved')
                        <a href="#" id="analyzeButton"
                            class="btn btn-success @if($posts->status === 'Pending') disabled @endif"
                            data-bs-toggle="modal"
                            data-bs-target="#analyzeModal"
                            data-id="{{ $posts->id }}"
                            data-name="{{ $posts->name }}"
                            data-branch="{{ $posts->branch_name ?? 'N/A' }}"
                            data-contact="{{ $posts->contact_number }}"
                            data-message="{{ $posts->message }}"
                            data-tasks='{{ json_encode($tasks) }}'>
                            ANALYZE
                        </a>
                        @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
    <!-- Analyze Modal Form -->
    <div class="modal fade" id="analyzeModal" tabindex="-1" aria-labelledby="analyzeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Changed this line to use 'modal-lg' -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="analyzeModalLabel">Analyze Concern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="analyzeForm" action="{{ route('concerns.endorsebm') }}" method="POST">
                    @csrf
                    @method('put')

                    <!-- Hidden input for the post ID -->
                    <input type="text" name="posts_id" id="posts_id" value="">


                    <!-- Prepared By - Hidden Field for Authenticated User -->
                    @if($authenticatedUser)
                    <input type="hidden" name="endorse_by" id="endorse_by" value="{{ $authenticatedUser['id'] }}">
                    @endif

                    <!-- Display Name -->
                    <div class="mb-3">
                        <label for="analyzePostName" class="form-label">Member Name</label>
                        <input type="text" class="form-control" id="analyzePostName" readonly>
                    </div>

                    <!-- Display Branch -->
                    <div class="mb-3">
                        <label for="analyzeBranch" class="form-label">Branch</label>
                        <input type="text" class="form-control" name="branch_name" id="analyzeBranch" readonly>
                    </div>

                    <!-- Display Contact Number -->
                    <div class="mb-3">
                        <label for="analyzeContact" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" name="contact_number" id="analyzeContact" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="analyzeMessage" class="form-label">Message</label>
                        <textarea class="form-control" name="message" id="analyzeMessage" rows="3" readonly></textarea>

                    </div>
                    <!-- Hidden Resolved Date -->

                    <!-- Tasks Section -->
                    <div class="mb-3">
                        <label class="form-label">Action Taken</label>
                        <div id="tasksContainer">
                            <!-- Initial Task -->

                            <input type="text" name="tasks[]" class="form-control mb-2" placeholder="Action 1" required>
                        </div>

                        <button type="button" id="addTaskButton" class="btn btn-secondary">Add Task</button>
                        <!-- <button type="button" id="removeTaskButton" class="btn btn-danger" style="display: none;">Less Task</button> -->
                    </div>

                    <!-- Buttons without inline JavaScript -->
                    <button id="resolvedButton" type="submit" class="btn btn-success">Resolved</button>
                    <button id="saveProgressButton" type="button" class="btn btn-primary">Save Progress</button>



                </form>
            </div>
        </div>
    </div>
    <!-- analyzeModal for resolved and save progress -->

</div>

<script>
$(document).ready(function () {
    let removedTasks = [];

    // When the analyze button is clicked, populate modal fields
    $(document).on('click', '#analyzeButton', function () {
        const postId = $(this).data('id');
        const postName = $(this).data('name');
        const postBranchName = $(this).data('branch');
        const postContact = $(this).data('contact');
        const postMessage = $(this).data('message');
        const existingTasks = $(this).data('tasks') || [];

        removedTasks = []; // Reset removed tasks

        $('#posts_id').val(postId);
        $('#analyzePostName').val(postName);
        $('#analyzeBranch').val(postBranchName || '');
        $('#analyzeContact').val(postContact || '');
        $('#analyzeMessage').val(postMessage || '');

        const tasksContainer = $('#tasksContainer');
        tasksContainer.empty();

        if (existingTasks.length > 0) {
            existingTasks.forEach((task, index) => appendTask(task, index + 1, tasksContainer));
            $('#removeTaskButton').show();
        } else {
            appendTask('', 1, tasksContainer);
            $('#removeTaskButton').hide();
        }
    });

    // Add Task
    $(document).on('click', '#addTaskButton', function () {
        const taskCount = $('#tasksContainer .task-item').length + 1;
        appendTask('', taskCount, $('#tasksContainer'));
        $('#removeTaskButton').show();
    });

    // Remove Task
    $(document).on('click', '.remove-task', function () {
        const taskInput = $(this).closest('.task-item').find('input[name="tasks[]"]');
        const taskValue = taskInput.val();
        if (taskValue) removedTasks.push(taskValue);
        $(this).closest('.task-item').remove();

        if ($('#tasksContainer .task-item').length === 0) {
            appendTask('', 1, $('#tasksContainer'));
            $('#removeTaskButton').hide();
        }
    });

    // Save Progress and Resolve buttons
    $('#saveProgressButton').on('click', function () {
        handleButtonClick('In Progress', $(this));
    });

    $('#resolvedButton').on('click', function () {
        handleButtonClick('Resolved', $(this));
    });

    function handleButtonClick(status, button) {
        button.prop('disabled', true).addClass('loading');
        submitForm(status, button);
    }

    function appendTask(task, index, container) {
        const taskHtml = `
            <div class="task-item mb-2 d-flex align-items-center">
                <input type="text" name="tasks[]" class="form-control me-2" placeholder="Action ${index}" value="${task}" required>
                <button type="button" class="btn btn-danger btn-sm remove-task">Remove</button>
            </div>`;
        container.append(taskHtml);
    }

    function submitForm(status, button) {
        const analyzeForm = $('#analyzeForm')[0];
        if (!analyzeForm) return console.error('Form not found');

        const formData = new FormData(analyzeForm);

        // Add tasks and removed tasks
        const tasks = getUniqueTasks();
        tasks.forEach(task => formData.append('tasks[]', task));
        removedTasks.forEach(task => formData.append('removed_tasks[]', task));

        formData.append('status', status);

        fetch('{{ route("posts.analyze") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            body: formData
        })
            .then(response => {
                if (!response.ok) throw new Error('Failed to submit form');
                return response.text();
            })
            .then(() => {
                window.location.href = '{{ route("concerns.resolvebm") }}';
            })
            .catch(error => {
                console.error('Error:', error);
                button.prop('disabled', false).removeClass('loading');
                alert('An error occurred. Please try again.');
            });
    }

    function getUniqueTasks() {
        const taskInputs = $('input[name="tasks[]"]');
        return [...new Set(taskInputs.map((_, input) => $(input).val().trim()).get())].filter(task => task);
    }
});


</script>
