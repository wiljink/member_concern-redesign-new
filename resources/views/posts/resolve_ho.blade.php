<x-app-layout>

    <!-- Rest of the Page Content -->
    <h1 class="text-4xl font-bold text-center">Validate Concern</h1>
    <div align='center'>
        @if(session()->has('success'))
            <div class="alert alert-{{ session('alert_type', 'success') }}">
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
                
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">ENDORSED BY</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">TASK</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">DAYS RESOLVED</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">DATE RESOLVED</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">RESOLVED BY</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">STATUS</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">MEMBER FEEDBACK</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">MEMBER ASSESSMENT</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">ACTION</th>
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
                            @php
                        $branch_name = $branch['branch_name'];
                        @endphp
                                {{ $branch['branch_name'] }}

                            @endif
                        @endforeach
                    </td>
                    <td>{{ $posts->contact_number }}</td>
                    <td>{{ $posts->created_at->format('Y-m-d') }}</td>
                    <td>{{ $posts->concern }}</td>
                    <td>{{ $posts->message }}</td>
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
                    <td>{{ $posts->resolved_days ? json_decode($posts->resolved_days, true)['days'] ?? 'N/A' : 'N/A' }} days</td>
                    <td>{{ $posts->resolved_date ?? 'N/A' }}</td>
                    <td>{{ $posts->resolve_by ?? 'N/A' }}</td>
                    <td>{{ $posts->status ?? 'Pending' }}</td>
                    <td>{{ $posts->member_comments ?? 'N/A' }}</td>
                    <td>{{ $posts->assess ?? 'N/A' }}</td>
                    <td>
            
                        @if($posts->status == 'Resolved')
                        <a href="#" id="validateButton"
                               class="btn btn-secondary"
                               data-bs-toggle="modal"
                               data-bs-target="#validateModal"
                               data-id="{{ $posts->id }}"
                               data-name="{{ $posts->name }}"
                               data-branch="{{ $branch_name }}"
                               data-contact="{{ $posts->contact_number }}"
                               data-message="{{ $posts->message }}"
                               data-concern="{{ $posts->concern ?? 'No concern available' }}"
                               data-assess="{{ $posts->assess }}"
                               data-member-comments="{{ $posts->member_comments }}"
                               data-tasks='{{ json_encode($tasks) }}'>
                                VALIDATE
                            </a>
                        @endif
                    
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>


    <!-- Validate Modal -->
    <div class="modal fade" id="validateModal" tabindex="-1" aria-labelledby="validateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="validateForm" method="POST" action="{{ route('validate.concern') }}">
                    @csrf
                    @method('post')

                    <div class="modal-header">
                        <h5 class="modal-title" id="validateModalLabel">Validate Concern</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden Input for Concern ID -->
                        <input type="hidden" name="id" id="validateConcernId">

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="validateName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="validateName" name="name" readonly>
                        </div>

                        <!-- Branch -->
                        <div class="mb-3">
                            <label for="validateBranch" class="form-label">Branch</label>
                            <input type="text" class="form-control" id="validateBranch" name="branch" readonly>
                        </div>

                        <!-- Concern -->
                        <div class="mb-3">
                            <label for="validateConcern" class="form-label">Concern</label>
                            <input type="text" class="form-control" id="validateConcern" name="concern" readonly>
                        </div>

                        <!-- Message -->
                        <div class="mb-3">
                            <label for="validateMessage" class="form-label">Message</label>
                            <textarea class="form-control" id="validateMessage" name="message" rows="3" readonly></textarea>
                        </div>

                        <!-- Task -->
                        <div class="mb-3">
                            <label for="validateTask" class="form-label">Tasks for the said concern</label>
                            <textarea id="validateTask" name="tasks" class="form-control" rows="5" readonly></textarea>
                        </div>

                        <!-- Assess the Task -->
                        <div class="mb-3">
                            <label for="rate" class="form-label">Assessment</label>
                            <select class="form-select" name="assess" id="assess" required>
                                <option value="" disabled selected>Select an option</option>
                                <option value="satisfied">Satisfied</option>
                                <option value="unsatisfied">Unsatisfied</option>
                                <option value="unresolved">Unresolved</option>
                            </select>
                        </div>


                        <!-- Member Concern -->
                        <div class="mb-3">
                            <label for="memberConcern" class="form-label">Member Comments</label>
                            <textarea class="form-control" id="memberComments" name="member_comments" rows="3" placeholder="Provide member feedback"></textarea>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const validateModal = document.getElementById('validateModal');
        const validateButtons = document.querySelectorAll('#validateButton');

        validateModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Button that triggered the modal

            // Extract data from data-* attributes
            const concernId = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const branch = button.getAttribute('data-branch');
            const concern = button.getAttribute('data-concern');
            const message = button.getAttribute('data-message');
            const tasks = JSON.parse(button.getAttribute('data-tasks')) || [];
            const assess = button.getAttribute('data-assess') || ''; // New field for assessment
            // Create an alert with the value
            // alert(`Assessment: ${assess}`);
            const memberComments = button.getAttribute('data-member-comments') || ''; // New field for member comments

            // Populate modal fields
            document.getElementById('validateConcernId').value = concernId;
            document.getElementById('validateName').value = name;
            document.getElementById('validateBranch').value = branch;
            document.getElementById('validateConcern').value = concern;
            document.getElementById('validateMessage').value = message;

            // Set assessment value
            document.getElementById('assess').value = assess;

            // Set member comments value
            document.getElementById('memberComments').value = memberComments;

            const taskList = document.getElementById('validateTask');
            taskList.innerHTML = ''; // Clear existing tasks

            tasks.forEach(task => {
                const listItem = document.createElement('li');
                listItem.textContent = task;
                listItem.classList.add('list-group-item');
                taskList.appendChild(listItem);
            });
        });

        validateButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Get tasks from the button's data-tasks attribute
                const tasks = JSON.parse(this.getAttribute('data-tasks'));
                const taskTextarea = document.getElementById('validateTask');

                // Populate the textarea with tasks, separated by line breaks
                taskTextarea.value = tasks.join('\n');
            });
        });
    });
</script>


</div>


