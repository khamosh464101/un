<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>معلومات ملکیت</title>
    <style>
        body {
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 0;
            /* font-family: 'dejavu sans', sans-serif; */
        }
        table {
            table-layout: fixed;
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
            vertical-align: middle;
        }
        img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .center {
            text-align: center;
        }
        .left {
            text-align: left;
        }
        .right {
            text-align: right;
        }
        .hidden-tr {
            display: none;
        }
    </style>
</head>
<body>
<!-- Top Logos Horizontal Row -->
<!-- Horizontal Logo Row for mPDF -->
<!-- Horizontal Logo Row for mPDF (No Borders, Larger Logos) -->
<table style="width: 100%; text-align: center; border: none;  direction: ltr;">
    <tr>
        <td style="width: 33.33%; text-align: left; border: none;">
            @php
                $logo_left = 'logos/ahf_logo.png';
            @endphp
            <img src="{{ public_path($logo_left) }}" alt="لوگو چپ" style="max-width: 140px; max-height: 140px;">
        </td>
        <td style="width: 33.33%; text-align: center; border: none;">
            @php
                $logo_center = 'logos/hlp_logo.jpg';
            @endphp
            <img src="{{ public_path($logo_center) }}" alt="لوگو مرکز" style="max-width: 160px; max-height: 160px;">
            <h3>Property Information</h3>
            <h3>معلومات ملکیت</h3>
        </td>
        <td style="width: 33.33%; text-align: right; border: none;">
            @php
                $logo_right = 'logos/habitat.png';
            @endphp
            <img src="{{ public_path($logo_right) }}" alt="لوگو راست" style="max-width: 140px; max-height: 140px;">
        </td>
    </tr>
</table>




<p>کود نمبر / Code Number:</p>

<!-- Location Table -->
<table>
    <tr>
        <td>پارسل/Parcel</td>
        <td>بلاک/Block</td>
        <td>گذر/Gozar</td>
        <td>ناحیه/District</td>
        <td>شهر/City</td>
        <td>ولایت/Province</td>
    </tr>
    <tr>
        <td>{{ $location['house'] ?? '' }}</td>
        <td>{{ $location['block'] ?? '' }}</td>
        <td>{{ $location['guzar'] ?? '' }}</td>
        <td>{{ $location['district_code'] ?? '' }}</td>
        <td>{{ $location['city_code'] ?? '' }}</td>
        <td>{{ $location['province_code'] ?? '' }}</td>
    </tr>
</table>

<br>

<!-- Displacement & Interviewee Info -->
<table>
    <tr>
        <td style="width:33%;">نام باشنده/Name of Occupier</td>
        <td style="width:33%;">{{ $submission->headFamily ? $submission->headFamily->hoh_name : $submission->interviewwee->inter_name }}</td>
        <td style="width:33%;" rowspan="4" class="center">
            @php
                $path = str_replace('http://127.0.0.1:8000/', '', $submission->photoSection->photo_interviewee);
            @endphp
            <img src="{{ public_path($path) }}" alt="تصویر" style="max-width: 120px; max-height: 150px;">
        </td>
    </tr>
    <tr>
        <td>نام پدر باشنده/Father Name of Occupier</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_father_name : $submission->interviewwee->inter_father_name }}</td>
    </tr>
    <tr>
    
        <td>نام پدر کلان باشنده/Grand Father Name</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_grandfather_name : $submission->interviewwee->inter_grandfather_name }}</td>
    </tr>
    <tr>
        <td>تذکره نمبر/ID Number</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_nic_number : $submission->interviewwee->inter_nic_number }}</td>
    </tr>

</table>

<br>

<table>
    <tr>
        <td>سکونت اصلی/Province of Origin</td>
        <td>{{ $location['province_origin'] ?? '' }}, {{ $location['district_origin'] ?? '' }}</td>
        <td>نوعیت مالکیت/Type of Occupancy</td>
        <td>{{ $location['house_owner'] ?? '' }}</td>
       
    </tr>
    <tr>
        <td> وضعیت بی جاه شدگی/Displacement Status</td>
        <td>{{ $location['status'] ?? '' }}</td>
        <td>نوعیت سنت دست داشته/ Land document type</td>
        <td>{{ $location['ownership_type'] ?? '' }}</td>
    </tr>
    <tr>
        <td> مدت بیجاه شدگی/Displacement Duration</td>
        <td>{{ $location['year'] ?? '' }}</td>
        <td>مدت اقامت/Duration of Occupation</td>
        <td>{{ $location['duration_lived_thishouse'] ?? '' }}</td>
    </tr>
    <tr>
    <td > مختصات ملکیت/GPS Coordinate</td>
    <td > Lat: {{ $submission->photoSection->latitude }}<br>Lon: {{ $submission->photoSection->longitude }}</td>
    <td>شماره تماس / Phone Number</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_phone_number : $submission->interviewwee->inter_phone_number }}</td>
    </tr>
</table>



<br>

<!-- Property Images -->
<table>
    <tr>
        <td class="center">عکس فضای ملکیت / Spatial Photo</td>
        <td class="center">عکس ملکیت / Property Photo</td>
    </tr>
    <tr>
        <td style="height:200px;" class="center">
            @php
                $map = str_replace('http://127.0.0.1:8000/', '', $location['map_image']);
            @endphp
            <img src="{{ public_path($map) }}" alt="تصویر" style="max-width:100%; max-height:200px;">
        </td>
        <td style="height:200px;" class="center">
            @php
                $house = str_replace('http://127.0.0.1:8000/', '', $submission->photoSection->photo_house_building);
            @endphp
            <img src="{{ public_path($house) }}" alt="تصویر" style="max-width:100%; max-height:200px;">
        </td>
    </tr>
</table>

</body>
</html>
