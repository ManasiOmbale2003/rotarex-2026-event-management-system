<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ROTAREX 2026 | Step 2 – Team Information</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
}
.container {
    max-width: 950px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
}
h2 {
    text-align: center;
    color: #f26a21;
}
label {
    font-weight: 600;
    margin-top: 15px;
    display: block;
}
input {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
th {
    background: #f0f0f0;
}
button {
    margin-top: 20px;
    padding: 12px 30px;
    background: #f26a21;
    color: #fff;
    border: none;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background: #d95717;
}
.instructions {
    background: #fff3e8;
    padding: 15px;
    margin-top: 20px;
    border-left: 5px solid #f26a21;
}
</style>
</head>

<body>

<div class="container">
<h2>Step 2: ROTAREX 2026 – Team Information & Signed Entry</h2>

<form action="step2_process.php" method="POST" enctype="multipart/form-data">

<label>Team Name *</label>
<input type="text" name="team_name" required>

<label>College Name *</label>
<input type="text" name="college_name" required>

<label>Branch *</label>
<input type="text" name="branch" required>

<h3>Team Members</h3>
<table>
<tr>
<th>Sr No</th>
<th>Student Name</th>
<th>Enrollment / PRN</th>
<th>Year</th>
<th>Mobile No</th>
</tr>

<?php for($i=1;$i<=4;$i++){ ?>
<tr>
<td><?= $i ?></td>
<td><input type="text" name="student_name[]"></td>
<td><input type="text" name="enrollment_prn[]"></td>
<td><input type="number" name="year[]" min="1" max="5"></td>
<td><input type="text" name="mobile_no[]"></td>
</tr>
<?php } ?>
</table>

<h3>Project Guide Information</h3>

<label>Guide Name *</label>
<input type="text" name="guide_name" required>

<label>Designation *</label>
<input type="text" name="designation" required>

<label>Contact Number *</label>
<input type="text" name="contact_number" required>

<label>Email *</label>
<input type="email" name="guide_email" required>

<div class="instructions">
<strong>Important Instructions:</strong>
<ol>
<li>Download and print the entry form after online submission.</li>
<li>Get the form signed by the Principal.</li>
<li>Affix official college stamp.</li>
<li>Scan the signed form clearly.</li>
<li>Upload the scanned copy in PDF format.</li>
</ol>
</div>

<label>Upload Signed Entry Form (PDF) *</label>
<input type="file" name="signed_entry" accept=".pdf" required>

<button type="submit">Save & Continue</button>

</form>
</div>

</body>
</html>
