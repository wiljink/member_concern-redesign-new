<x-app-layout>
    <!-- Rest of the Page Content -->
    <h1 align='center'>Member Concern</h1>
    <div align='center'>
        @if(session()->has('success'))
        <div class="alert alert-success">
            {!! session('success') !!}
        </div>
        @endif
    </div>


    <div class="container d-flex flex-column align-items-center my-4" style="min-height: 100vh;">

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">NAME</th>
                    <th scope="col">BRANCH</th>
                    <th scope="col">CONTACT NUMBER</th>
                    <th scope="col">CONCERN RECEIVED DATE</th>
                    <th scope="col">CONCERN</th>
                    <th scope="col">MESSAGE</th>
                    @if($authenticatedUser['account_type_id'] == 7)
                    <th scope="col">ENDORSED BY</th>
                    <th scope="col">TASK</th>
                    <th scope="col">DAYS RESOLVED</th>
                    <th scope="col">DATE RESOLVED</th>
                    <th scope="col">RESOLVED BY</th>
                    <th scope="col">STATUS</th>
                    @endif
                    <th scope="col">ACTION</th>
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
                    <td>{{ $posts->endorse_by_fullname ?? 'N/A' }}</td>
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
                            data-branch="{{ $branch['branch_name'] }}"
                            data-contact="{{ $posts->contact_number }}"
                            data-message="{{ $posts->message }}"
                            data-tasks='{{ json_encode($tasks) }}'>
                            ANALYZE
                        </a>
                        @else
                        <a href="#" id="validateButton"
                            class="btn btn-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#validateModal"
                            data-id="{{ $posts->id }}"
                            data-name="{{ $posts->name }}"
                            data-branch="{{ $branch['branch_name'] }}"
                            data-contact="{{ $posts->contact_number }}"
                            data-message="{{ $posts->message }}">
                            VALIDATE
                        </a>
                        @endif
                        @else
                        @if($posts->status !== 'Resolved')
                        <a href="#" id="endorseButton"
                            class="btn btn-primary @if(in_array($posts->status, ['Endorsed', 'In Progress'])) disabled @endif"
                            data-bs-toggle="modal"
                            data-bs-target="#endorseModal"
                            data-id="{{ $posts->id }}"
                            data-branch="{{ $posts->branch }}"
                            data-branch-manager-id="{{ optional($posts->branch_manager)->id }}">
                            ENDORSE
                        </a>
                        @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>




        <!-- Pagination Links -->
        <div class="d-flex justify-content-center">
            {{ $data->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>

    <!-- endorseModal Form and disable endorse link -->
    <script>
        $(document).ready(function() {
            // Handle form submission for endorsing
            $('#endorseForm').on('submit', function(event) {
                event.preventDefault(); // Prevent default form submission

                const formData = $(this).serialize(); // Serialize the form data
                const endorseButton = $('#endorseButton'); // Get the endorse button

                // Disable the button immediately after form submission
                endorseButton.addClass('disabled').attr('disabled', true).text('Endorsed');

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'), // Form action URL
                    data: formData,
                    success: function(response) {
                        // Check if the response indicates success
                        if (response.success) {
                            // Hide the modal after success
                            $('#endorseModal').modal('hide');

                            // Update the button state on the page
                            endorseButton.text('Endorsed').attr('disabled', true).addClass('disabled');

                            // Redirect to the posts.index page after success
                            window.location.href = '{{ route('
                            posts.index ') }}'; // Redirect to index page
                        } else {
                            alert('An error occurred: ' + response.message);
                            // Re-enable the button if there was an error
                            endorseButton.removeClass('disabled').attr('disabled', false).text('Endorse');
                        }
                    },
                    error: function() {
                        alert('An error occurred while submitting the endorsement.');
                        // Re-enable the button if there was an error
                        endorseButton.removeClass('disabled').attr('disabled', false).text('Endorse');
                    }
                });
            });

            // Populate the endorse modal with post ID and branch manager
            $('#endorseModal').on('show.bs.modal', function(event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var postId = button.getAttribute('data-id'); // Extract the post ID
                var branchId = button.getAttribute('data-branch'); // Extract the branch ID

                // Populate the hidden input with the post ID
                document.getElementById('post_id').value = postId;

                // Pre-select the branch manager in the dropdown
                var endorseToSelect = document.getElementById('endorseTo');
                for (let i = 0; i < endorseToSelect.options.length; i++) {
                    const option = endorseToSelect.options[i];
                    if (option.getAttribute('data-branch-id') === branchId) {
                        option.selected = true;
                        break;
                    }
                }
            });
        });
    </script>

</x-app-layout>