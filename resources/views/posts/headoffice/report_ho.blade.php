<x-app-layout>
    <div class="container mt-5">
        <h3 class="mb-3 text-center" style="font-family: 'Poppins', sans-serif; font-weight: 700;">
            Average Concern All Branches
        </h3>

        <!-- Main Area Dropdown -->
        <div class="mb-3 text-center">
            <label for="areaSelect" class="form-label fw-bold">Select Area:</label>
            <select id="areaSelect" class="form-select w-auto d-inline-block">
                <option value="" selected>Select Area</option>
                <option value="area1">Area 1</option>
                <option value="area2">Area 2</option>
                <option value="area3">Area 3</option>
                <option value="all">All Areas</option>
            </select>
        </div>

        <!-- Secondary Dropdowns for Specific Locations -->
        <div class="mb-3 text-center hidden" id="area1Dropdown">
            <label for="area1Select" class="form-label fw-bold">Select Branch (Area 1):</label>
            <select id="area1Select" class="form-select w-auto d-inline-block">
                <option value="" selected>Select Branch</option>
                <option value="yacapin">Yacapin</option>
                <option value="agora">Agora</option>
                <option value="carmen">Carmen</option>
            </select>
        </div>

        <div class="mb-3 text-center hidden" id="area2Dropdown">
            <label for="area2Select" class="form-label fw-bold">Select Branch (Area 2):</label>
            <select id="area2Select" class="form-select w-auto d-inline-block">
                <option value="" selected>Select Branch</option>
                <option value="tagbilaran">Tagbilaran</option>
                <option value="ubay">Ubay</option>
                <option value="tubigon">Tubigon</option>
            </select>
        </div>

        <div class="mb-3 text-center hidden" id="area3Dropdown">
            <label for="area3Select" class="form-label fw-bold">Select Branch (Area 3):</label>
            <select id="area3Select" class="form-select w-auto d-inline-block">
                <option value="" selected>Select Branch</option>
                <option value="aglayan">Aglayan</option>
                <option value="valencia">Valencia</option>
                <option value="maramag">Maramag</option>
            </select>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Concern Type</th>
                    <th>Avg. Days to Resolve</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Loans</td>
                    <td>5</td>
                    <td><a href="documents/loans_report.pdf" class="btn btn-success btn-sm" download>Download</a></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Deposit</td>
                    <td>3</td>
                    <td><a href="documents/deposit_report.pdf" class="btn btn-success btn-sm" download>Download</a></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Customer Service</td>
                    <td>2</td>
                    <td><a href="documents/customer_service_report.pdf" class="btn btn-success btn-sm" download>Download</a></td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>General</td>
                    <td>4</td>
                    <td><a href="documents/general_report.pdf" class="btn btn-success btn-sm" download>Download</a></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById("areaSelect").addEventListener("change", function () {
            let selectedValue = this.value;
            
            // Hide all secondary dropdowns
            document.getElementById("area1Dropdown").classList.add("hidden");
            document.getElementById("area2Dropdown").classList.add("hidden");
            document.getElementById("area3Dropdown").classList.add("hidden");

            // Show relevant dropdown based on selection
            if (selectedValue === "area1") {
                document.getElementById("area1Dropdown").classList.remove("hidden");
            } else if (selectedValue === "area2") {
                document.getElementById("area2Dropdown").classList.remove("hidden");
            } else if (selectedValue === "area3") {
                document.getElementById("area3Dropdown").classList.remove("hidden");
            }
        });
    </script>

</x-app-layout>