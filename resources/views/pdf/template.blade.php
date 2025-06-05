<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>سند ملکیت</title>
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
        .hidden-tr {
            display: none;
        }
    </style>
</head>
<body>

<div class="center">
    <h2>سند ملکیت</h2>
    <h2>Land Ownership Deed</h2>
</div>

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
        <td style="width:33%;">سکونت اصلی/Place of Origin</td>
        <td style="width:33%;">{{ $location['province_origin'] ?? '' }}, {{ $location['district_origin'] ?? '' }}</td>
        <td style="width:33%;" rowspan="3" class="center">
            @php
                $path = str_replace('http://127.0.0.1:8000/', '', $submission->photoSection->photo_interviewee);
            @endphp
            <img src="{{ public_path($path) }}" alt="تصویر" style="max-width: 120px; max-height: 150px;">
        </td>
    </tr>
    <tr>
        <td>نوعیت بیجاه شده/Type of Displacement</td>
        <td>{{ $location['status'] ?? '' }}</td>
    </tr>
    <tr>
        <td>مدت بیجاه شده/Displacement Duration</td>
        <td>{{ $location['year'] ?? '' }}</td>
    </tr>
</table>

<br>

<!-- Personal and Property Info -->
<table>
    <tr>
        <td colspan="2">مشخصات شخصی / Personal Information</td>
        <td colspan="2">مشخصات حقوقی و فزیکی ملکیت / Land Ownership</td>
    </tr>
    <tr>
        <td style="width:25%;">نام / Name</td>
        <td style="width:25%;">{{ $submission->headFamily ? $submission->headFamily->hoh_name : $submission->interviewwee->inter_name }}</td>
        <td>نوعیت مالکیت / Type of Ownership</td>
        <td>{{ $location['ownership_type'] ?? '' }}</td>
    </tr>
    <tr>
        <td>نام پدر / Father Name</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_father_name : $submission->interviewwee->inter_father_name }}</td>
        <td>اسناد دست داشته / Land Deed Type</td>
        <td></td>
    </tr>
    <tr>
        <td>نام پدر کلان / Grandfather Name</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_grandfather_name : $submission->interviewwee->inter_grandfather_name }}</td>
        <td>نوعیت ملکیت / Land Type</td>
        <td></td>
    </tr>
    <tr>
        <td>نمبر تذکره / ID Number</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_nic_number : $submission->interviewwee->inter_nic_number }}</td>
        <td>مختصات ملکیت / GPS Coordinates</td>
        <td>Lat: {{ $submission->photoSection->latitude }}<br>Lon: {{ $submission->photoSection->longitude }}</td>
    </tr>
    <tr>
        <td>شماره تماس / Phone Number</td>
        <td>{{ $submission->headFamily ? $submission->headFamily->hoh_phone_number : $submission->interviewwee->inter_phone_number }}</td>
        <td></td>
        <td></td>
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
