<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        /* Custom styles */
        .custom-container {
            background-color: #004d00; /* Dark green background */
            padding: 30px;
            border-radius: 10px;
            color: #fff; /* White text for better contrast */
        }

        .custom-form {
            border: 1px solid #ccc; /* Border around the form */
            padding: 20px;
            border-radius: 5px;
            background-color: #fff; /* White background for the form */
            color: #000; /* Default black text for form content */
        }

        /* Initially hide the concern and message fields */
        #concernAndMessageField {
            display: none; /* Hidden by default */
        }

        /* Custom styles for buttons */
        .button-container {
            margin-top: 20px;
            text-align: center;
        }

        .toggle-concern {
            margin: 10px 5px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Add specific styles for different concern types */
        .toggle-concern.loans-btn {
            background-color: #007bff; /* Bootstrap primary color */
            color: white;
        }

        .toggle-concern.deposits-btn {
            background-color: #28a745; /* Bootstrap success color */
            color: white;
        }

        .toggle-concern.customer-service-btn {
            background-color: #ffc107; /* Bootstrap warning color */
            color: black;
        }

        .toggle-concern.general-btn {
            background-color: #6c757d; /* Bootstrap secondary color */
            color: white;
        }

        /* Hover effects */
        .toggle-concern:hover {
            opacity: 0.9;
        }

        /* Active button effect */
        .toggle-concern:active {
            transform: scale(0.98);
        }
    </style>
    <title>Create Post</title>
</head>
<body>
    <h1 align='center'>Create Concern</h1>
    <div align='center'>
       
    @if(session()->has('success'))
    <div class="alert alert-success">
        {!! session('success') !!}
    </div>
        @endif
    </div>

    <div class="container custom-container mt-5">
        <form method="post" action="{{ route('posts.store') }}" class="custom-form">
            @csrf
            @method('post')
            
            <div class="form-group">
                <label for="exampleInputEmail1">Name</label>
                <input type="text" name="name" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Name">
                <small id="emailHelp" class="form-text text-muted">Input Name</small>
            </div>

            <div class="form-group">
                <label for="exampleFormControlSelect1">Branch</label>
                <select class="form-control" id="exampleFormControlSelect1" name="branch">
                    <option value="" selected>Select Branch</option>
                    @forelse($branches as $branch)
                        <option value="{{ $branch['id'] }}">{{ $branch['branch_name'] }}</option>
                    @empty
                        <option value="">No branches available</option>
                    @endforelse
                </select>
            </div>

            <div class="form-group">
                <label for="exampleInputPassword1">Contact Number</label>
                <input type="tel" class="form-control" id="exampleInputPassword1" placeholder="Contact Number" name="contact_number">
            </div>

            <div class="form-group" id="concernAndMessageField">
                <div>
                    <label for="exampleFormControlSelect2">Concern:(Pili-a imung concern)</label>
                    <select class="form-control" id="exampleFormControlSelect2" name="concern">
                        <option value="" selected>Select Concern</option>
                        <option value="Loans">Loans</option>
                        <option value="Deposit">Deposits</option>
                        <option value="Customer Service">Customer Service</option>
                        <option value="General">General</option>
                    </select>
                </div>
                
                <div>
                    <label for="exampleFormControlTextarea1" class="form-label">Message:(E-detalye imung Concern)</label>
                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" name="message"></textarea>
                </div>
            </div>

            <div class="button-container">
                <!-- Toggle Buttons -->
                <label>Select Concern Type:</label><br>
                <button type="button" class="btn toggle-concern loans-btn" data-value="Loans">Loans</button>
                <button type="button" class="btn toggle-concern deposits-btn" data-value="Deposit">Deposits</button>
                <button type="button" class="btn toggle-concern customer-service-btn" data-value="Customer Service">Customer Service</button>
                <button type="button" class="btn toggle-concern general-btn" data-value="General">General</button>
            </div>

            <div align="center" style="margin-top: 20px;">
                <!-- Submit Button initially disabled -->
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Submit</button>
            </div>
        </form>
    </div>

    <script>

   document.addEventListener('DOMContentLoaded', function () {
    // Initially hide concern and message fields
    const concernAndMessageField = document.getElementById('concernAndMessageField');
    const toggleButtons = document.querySelectorAll('.toggle-concern');
    const concernSelect = document.getElementById('exampleFormControlSelect2');
    const buttonContainer = document.querySelector('.button-container');
    const submitBtn = document.getElementById('submitBtn');

    // Toggle visibility on button click, set the concern value, and hide buttons
    toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
            concernAndMessageField.style.display = 'block';

            // Use data-value attribute to set the selected value in the dropdown
            concernSelect.value = this.getAttribute('data-value');

            // Hide all buttons after selection
            buttonContainer.style.display = 'none';

            // Enable the submit button
            submitBtn.disabled = false;
        });
    });
});

    </script>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9UibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>
