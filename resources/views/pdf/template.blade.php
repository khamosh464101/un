<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title></title>
    <style>
        body {
            direction: rtl;
            text-align: right;
            margin:0px;
            padding:0px;
        }
        table {
            table-layout: fixed;
            border-collapse: collapse;
            width:100%;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 12px;
        }
        img {
            max-width: 120px;
        }
        tr.hidden-tr{
            display:none;
            opacity: 0;
            visibilty: hidden;
        }
    </style>
</head>
<body>

<div style="text-align:center">
    <h2>سند ملکیت</h2>
    <h2>Land Ownership Deed</h2>
</div>
<div style="text-align:right;">
    <p>کود نمبر / Code Number:</p>
</div>

<div style="position:fixed; top:115px; ">
    <table>
        <tr>
            <td style="width:14.28%"> واحد /House Number</td>
            <td style="width:14.28%">پارسل/Parcel</td>
            <td style="width:14.28%">بلاک/Block</td>
            <td style="width:14.28%">گذر/Gozar</td>
            <td style="width:14.28%">ناحیه/District</td>
            <td style="width:14.28%">شهر/City</td>
            <td style="width:14.28%">ولایت/Province</td>

        </tr>
        <tr class="">
        <td>{{ $location['house'] ?? '' }}</td>
        <td>01</td>
        <td>{{ $location['block'] ?? '' }}</td>
        <td>{{ $location['guzar'] ?? '' }}</td>
        <td>{{ $location['nahya'] ?? '' }}</td>
        <td>{{ $location['district'] ?? '' }}</td>
        <td>{{ $location['province'] ?? '' }}</td>    
        </tr>
    </table>
</div>
<div style="position:fixed; top:230px;" cellpadding="5px" autosize="1"  width="100%">
        <table>
            <tr>
                <td style="width:33.33%;">سکونت اصلی/Place of Origin </td>
                <td style="width:33.33%;">{{ $location['province_origin'] ?? '' }}, {{ $location['district_origin'] ?? '' }}</td>
                <td style="width:33.33%;" rowspan="3" style="margin: 0 auto; text-align:center;">
                @php
                    $path = str_replace('http://127.0.0.1:8000/', '', $submission->photoSection->photo_interviewee);
                @endphp
                <img src="{{ public_path($path) }}" alt="تصویر" style="margin: 0 auto; transform: rotate(90deg) scale(1.2);" width="200px" >
                </td>
            </tr>
            <tr>
                <td>نوعیت بیجاه شده/Type of Displacement</td>
                <td>{{ $location['status'] ?? '' }}</td>
        
            </tr>
            <tr >
                <td >مدت بیجاه شده/Displacement Duration</td>
                <td>{{ $location['year'] ?? '' }}</td>
            </tr>
        </table>
</div>
<div style="position:fixed; top:410px">
<table  width="100%" style="border-collapse: collapse; text-align: center;">
    <tr>
        <td colspan="2">مشخصات شخصی / Personal Information</td>
        <td colspan="2">مشخصات حقوقی و فزیکی ملکیت / Land Ownership</td>
    </tr>
    <tr>
        <td style="width:25%;">نام / Name</td>
        <td style="width:25%;">{{$submission->headFamily ? $submission->headFamily->hoh_name : $submission->interviewwee->inter_name}}</td>
        <td>نوعیت مالکیت / type of ownership</td>
        <td>{{ $location['ownership_type'] ?? '' }}</td>
    </tr>
    <tr>
        <td>نام پدر / Father Name</td>
        <td>{{$submission->headFamily ? $submission->headFamily->hoh_father_name : $submission->interviewwee->inter_father_name}}</td>
        <td>اسناد دست داشته / Land deed type</td>
        <td></td>
    </tr>
    <tr>
        <td>نام پدر کلان / Father Name</td>
        <td>{{$submission->headFamily ? $submission->headFamily->hoh_grandfather_name : $submission->interviewwee->inter_grandfather_name}}</td>
        <td>نوعیت ملکیت / Land Type</td>
        <td></td>
    </tr>
    <tr>
        <td>نمبر تذکره / ID Number</td>
        <td>{{$submission->headFamily ? $submission->headFamily->hoh_nic_number : $submission->interviewwee->inter_nic_number}}</td>
        <td style="width:25%;">گردیدات مالکیت / GPS Coordinate</td>
        <td style="width:25%;">
        Lat: {{$submission->photoSection->latitude}}
        Lan: {{$submission->photoSection->longitude}}
        </td>
    </tr>
    <tr>
        <td colspan="2"></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td >شماره تماس / Phone Number</td>
        <td>{{$submission->headFamily ? $submission->headFamily->hoh_phone_number : $submission->interviewwee->inter_phone_number}}</td>
        <td></td>
        <td></td>
    </tr>
</table>

</div>

<div style="position:fixed; top: 770px">
       <table>
         <tr>
            <td>عکس فضای ملکیت/Spatial Photo</td>
            <td>عکس ملکیت/ Property Photo</td>
        </tr>
        <tr>
            <td style="text-align:center; height:200px;">
                @php
                    $map = str_replace('http://127.0.0.1:8000/', '', $submission->map_image);
                @endphp
                <img style=" transform:scale(2);" src="{{ public_path($map) }}" alt="تصویر"   >
            </td>
            <td style="text-align:center; height:200px;">
                @php
                    $house = str_replace('http://127.0.0.1:8000/', '', $submission->photoSection->photo_house_building);
                @endphp
                <img style="transform:scale(2);" src="{{ public_path($house) }}" alt="تصویر"   >
            </td>
        </tr>
       </table>
</div>
</body>
</html>