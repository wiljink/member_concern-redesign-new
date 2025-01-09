<x-app-layout>
<h1 class="text-4xl font-bold text-center">Member Concern</h1>

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
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">STATUS</th>
                    <th scope="col" class="border bg-slate-200 text-center font-poppins font-bold">ACTION</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $post)
                <tr>
                    <td class="border text-center">{{ $post->id }}</td>
                    <td class="border text-center">{{ $post->name }}</td>
                    <td class="border text-center">
                        @foreach($branches as $branch)
                        @if($post->branch == $branch['id'])
                        {{ $branch['branch_name'] }}
                        @endif
                        @endforeach
                    </td>
                    <td class="border text-center">{{ $post->contact_number }}</td>
                    <td class="border text-center">{{ $post->created_at->format('Y-m-d') }}</td>
                    <td class="border text-center">{{ $post->concern }}</td>
                    <td class="border text-center">{{ $post->message }}</td>
                    <td class="border text-center">{{ $post->status ?? 'Pending' }}</td>
                    <td class="border text-center">
                            <a href="#" id="endorseButton"
                            class="btn btn-primary @if(in_array($post->status, ['Endorsed', 'In Progress'])) disabled @endif"
                            data-bs-toggle="modal"
                            data-bs-target="#endorseModal"
                            data-id="{{ $post->id }}"
                            data-branch="{{ $post->branch }}"
                            @if($post->status == 'Endorsed') disabled @endif>
                            ENDORSE
                            </a>
                        </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal Form for Endorse staff -->
    <div class="modal fade" id="endorseModal" tabindex="-1" aria-labelledby="endorseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="endorseModalLabel">Endorse Concern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="endorseForm" action="{{ route('posts.update') }}" method="POST">
                        @csrf
                        @method('put')

                        <!-- Hidden input for the post ID -->
                        <input type="hidden" name="post_id" id="post_id">

                        <!-- Endorse To - Select Dropdown -->
                        <div class="mb-3">
                            <label for="endorseTo" class="form-label">Endorse To</label>
                            <select class="form-select" id="endorseTo" name="endorse_to" required>
                                <option value="" selected disabled>Select Branch Manager</option>
                                @foreach($branches as $branch)
                                @if($branch['id'] == 23)
                                @foreach($branch['head_office_management'] as $manager)
                                <option value="{{ $manager['id'] }}" data-branch-id="{{ $branch['id'] }}">
                                    {{ $manager['fullname'] }}
                                </option>
                                @endforeach
                                @else
                                <option value="{{ $branch['id'] }}">{{
                                    optional($branch['branch_manager'])['fullname'] }}</option>
                                @endif
                                @endforeach

                            </select>
                        </div>

                        <input type="hidden" id="endorsedDate" name="endorsed_date">

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success" id="submitEndorsement">Submit Endorsement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- endorseModal Form and disable endorse link -->
    <script>
    $(document).ready(function() {

        // Populate the endorse modal with post ID and branch manager
        $(document).on('click', '#endorseButton', function() {
            branch = $(this).attr('data-branch');
            post_id = $(this).attr('data-id');
                
            $('#endorseTo').val(branch);
            $('#post_id').val(post_id);
        });

       
    });
</script>

</x-app-layout>
