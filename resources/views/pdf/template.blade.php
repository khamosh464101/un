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
            font-family: 'dejavu sans', sans-serif;
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

@php
    // Ensure all location keys exist with defaults
    $defaultLocation = [
        'house' => '',
        'block' => '',
        'guzar' => '',
        'district_code' => '',
        'city_code' => '',
        'province_code' => '',
        'province_origin' => '',
        'district_origin' => '',
        'house_owner' => '',
        'status' => '',
        'ownership_type' => '',
        'year' => '',
        'duration_lived_thishouse' => '',
        'map_image' => ''
    ];
    $location = array_merge($defaultLocation, $location ?? []);
    
    // Fix ownership type text if needed
    if (isset($location['ownership_type']) && $location['ownership_type'] === 'سند تصدی مرسوم [ فروشنده ملک، رسید فروش، ارث و غیره]') {
        $location['ownership_type'] = 'سند تصدی مرسوم';
    }
    
    // Helper function to convert URL to filesystem path for mPDF
    function getImagePath($path) {
        if (empty($path)) {
            return '';
        }
        // Remove http://127.0.0.1:8000/ if present
        $path = str_replace('http://127.0.0.1:8000/', '', $path);
        $path = str_replace('http://localhost:8000/', '', $path);
        $path = str_replace('https://', '', $path);
        
        // Check if it's a storage path
        if (str_starts_with($path, 'storage/')) {
            return public_path($path);
        }
        
        // Check if file exists in public path
        if (file_exists(public_path($path))) {
            return public_path($path);
        }
        
        // Return as is (might be external URL)
        return $path;
    }
    
    // Get interviewee name safely
    $intervieweeName = $submission->headFamily->hoh_name ?? 
                       $submission->interviewwee->inter_name ?? 
                       'N/A';
    
    $intervieweeFatherName = $submission->headFamily->hoh_father_name ?? 
                             $submission->interviewwee->inter_father_name ?? 
                             'N/A';
    
    $intervieweeGrandfatherName = $submission->headFamily->hoh_grandfather_name ?? 
                                   $submission->interviewwee->inter_grandfather_name ?? 
                                   'N/A';
    
    $intervieweeNicNumber = $submission->headFamily->hoh_nic_number ?? 
                            $submission->interviewwee->inter_nic_number ?? 
                            'N/A';
    
    $intervieweePhone = $submission->headFamily->hoh_phone_number ?? 
                        $submission->interviewwee->inter_phone_number ?? 
                        'N/A';
    
    // Get photo paths
    $photoInterviewee = getImagePath($submission->photoSection->photo_interviewee ?? '');
    $photoDocument = getImagePath($submission->houseLandOwnership?->landOwnershipDocument?->first()?->house_document_photo ?? '');
    $photoHouse = getImagePath($submission->photoSection->photo_house_building ?? '');
    $mapImage = getImagePath($location['map_image'] ?? '');
@endphp

<!-- Top Logos Horizontal Row -->
<table style="width: 100%; text-align: center; border: none; direction: ltr;">
    <tr>
        <td style="width: 80%; text-align: center; border: none;">
            <div style="margin-bottom: 20px;">
                <img src="{{ public_path('logos/ahf_logo.png') }}" alt="لوگو چپ" style="max-width: 170px; max-height: 170px; display: inline-block; margin-bottom: 10px;">
                <img src="{{ public_path('logos/hlp_logo.jpg') }}" alt="لوگو مرکز" style="max-width: 130px; max-height: 130px; display: inline-block; margin-left: 20px; margin-right: 30px;">
                <img src="{{ public_path('logos/habitat.png') }}" alt="لوگو راست" style="max-width: 50px; max-height: 50px; display: inline-block; margin-bottom: 10px;">
            </div>
            <h3 style="margin-top: 40px; display: block;">Property Information <span style="color:#00BFFF; font-weight:bold;">|</span> معلومات ملکیت</h3>
        </td>
    </tr>
</table>

<!-- Code Number Row -->
<table style="width: 100%; border: none; font-weight: 700;">
    <tr>
        <td style="text-align: right; border:none;">کود نمبر:</td>
        <td style="text-align: left; border:none; direction: ltr">
            <strong style="direction: ltr; unicode-bidi: embed;">Issue year: 2024</strong>
            <br>
            Code Number: {{ $submission->code_number ?? 'N/A' }}
        </td>
    </tr>
</table>

<!-- Location Table -->
<table>
    <tr>
        <td>قطعه زمین-Parcel</td>
        <td>بلاک-Block</td>
        <td>گذر-Gozar</td>
        <td>ناحیه-District</td>
        <td>شهر-City</td>
        <td>ولایت-Province</td>
    </tr>
    <tr>
        <td>{{ $location['house'] }}</td>
        <td>{{ $location['block'] }}</td>
        <td>{{ $location['guzar'] }}</td>
        <td>{{ $location['district_code'] }}</td>
        <td>{{ $location['city_code'] }}</td>
        <td>{{ $location['province_code'] }}</td>
    </tr>
</table>

<br>

<!-- Displacement & Interviewee Info -->
<table>
    <tr>
        <td style="width: 42%;">
            <table style="width: 100%; border: none; padding:0;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">نام باشنده</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Name (of occupier)</td>
                </tr>
            </table>
        </td>
        <td style="width:30%;">{{ $intervieweeName }}</td>
        <td style="width:27%;" rowspan="4" class="center">
            @if($photoInterviewee && file_exists($photoInterviewee))
                <img src="{{ $photoInterviewee }}" alt="تصویر" style="max-width: 120px; max-height: 150px;">
            @else
                <div style="max-width: 120px; max-height: 150px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">No Image</div>
            @endif
        </td>
    </tr>
    <tr>
        <td style="width: 42%;">
            <table style="width: 100%; border: none; padding:0;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">نام پدر باشنده</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Father's name (of occupier)</td>
                </tr>
            </table>
        </td>
        <td>{{ $intervieweeFatherName }}</td>
    </tr>
    <tr>
        <td style="width: 42%;">
            <table style="width: 100%; border: none; padding:0;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">نام پدرکلان باشنده</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Grandfather's name (of occupier)</td>
                </tr>
            </table>
        </td>
        <td>{{ $intervieweeGrandfatherName }}</td>
    </tr>
    <tr>
        <td style="width: 42%;">
            <table style="width: 100%; border: none; padding:0;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">تذکره نمبر</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">ID number</td>
                </tr>
            </table>
        </td>
        <td>{{ $intervieweeNicNumber }}</td>
    </tr>
</table>

<br>

<!-- Additional Info Table -->
<table>
    <tr>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">سکونت اصلی</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Province of origin</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['province_origin'] }}, {{ $location['district_origin'] }}</td>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">مالک زمین</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">House / Land Ownership</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['house_owner'] }}</td>
    </tr>
    <tr>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">وضعیت بیجاه شدگی</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Displacement status</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['status'] }}</td>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">اسناد زمین</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Land document</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['ownership_type'] }}</td>
    </tr>
    <tr>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">مدت بیجاه شدگی</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Displacement duration</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['year'] }}</td>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">مدت اقامت</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Duration of occupation</td>
                </tr>
            </table>
        </td>
        <td>{{ $location['duration_lived_thishouse'] }}</td>
    </tr>
    <tr>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">مختصات ملکیت</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">GPS coordinate</td>
                </tr>
            </table>
        </td>
        <td>Lat: {{ $submission->photoSection->latitude ?? 'N/A' }}<br>Lon: {{ $submission->photoSection->longitude ?? 'N/A' }}</td>
        <td>
            <table style="width: 100%; border: none; padding:none;">
                <tr style="padding:none;">
                    <td style="text-align: right; direction: rtl; border:none; padding:none;">شماره تماس</td>
                </tr>
                <tr>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding:none;">Phone number</td>
                </tr>
            </table>
        </td>
        <td>{{ $intervieweePhone }}</td>
    </tr>
</table>

<br>

<!-- Property Images -->
<table>
    <tr>
        <td class="center">
            <table style="width: 100%; border: none; padding:0; font-size: 16px;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">عکس سند ملکیت</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Land document</td>
                </tr>
            </table>
        </td>
        <td class="center">
            <table style="width: 100%; border: none; padding:0; font-size: 16px;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">عکس فضای ملکیت</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Satellite image</td>
                </tr>
            </table>
        </td>
        <td class="center">
            <table style="width: 100%; border: none; padding:0; font-size: 16px;">
                <tr style="padding:0">
                    <td style="text-align: right; direction: rtl; unicode-bidi: embed; width: 40%; border:none; padding:0;">عکس ملکیت</td>
                    <td style="text-align: left; direction: ltr; unicode-bidi: embed; width: 60%; border:none; padding:0;">Property image</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="height:250px;" class="center">
            @if($photoDocument && file_exists($photoDocument))
                <img src="{{ $photoDocument }}" alt="سند ملکیت" style="max-width:100%; max-height:250px;">
            @else
                <div style="max-width:100%; max-height:250px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">No Image</div>
            @endif
        </td>
        <td style="height:250px;" class="center">
            @if($mapImage && file_exists($mapImage))
                <img src="{{ $mapImage }}" alt="نقشه" style="max-width:100%; max-height:250px;">
            @else
                <div style="max-width:100%; max-height:250px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">No Map Image</div>
            @endif
        </td>
        <td style="height:250px;" class="center">
            @if($photoHouse && file_exists($photoHouse))
                <img src="{{ $photoHouse }}" alt="تصویر ملکیت" style="max-width:100%; max-height:250px;">
            @else
                <div style="max-width:100%; max-height:250px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">No Image</div>
            @endif
        </td>
    </tr>
</table>

<!-- Notes Section -->
<table style="border: none;">
    <tr>
        <td style="text-align: left; direction: ltr; unicode-bidi: embed; border: none; padding: 5px; font-size: 12px; line-height: 1.2;">
            Note: This guidance note is not an official and/or legal document but provides information and analysis collected by UN-Habitat
            on housing and land characteristics. For further information or to update the information please contact UN-Habitat. 
            <br>Email: <span style="color:#00BFFF;">info.unhafg@un.org</span>
        </td>
    </tr>
    <tr>
        <td style="text-align: right; direction: rtl; border:none; padding: 5px; font-size: 12px; line-height: 1.2;">
            یادداشت:
            این ورق یک سند رسمی و یا حقوقی نمی باشد، بلکه مجموعه معلومات و تحلیل هایی است که از طرف دفتر اسکان بشر ملل متحد در رابطه به خصوصیات مسکن و زمین جمع
            آوری گردیده است. برای دریافت معلومات بیشتر در مورد این ورق لطفآ با دفتر اسکان بشر ملل متحد تماس بیگیرید . ایمیل ادرس : <span style="color:#00BFFF;">info.unhafg@un.org</span>
        </td>
    </tr>
</table>

</body>
</html>