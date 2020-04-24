<?php
$BmpFilenameStr = $_GET['filename'];
$GcodeStr = '';
addStartRoutine($GcodeStr);
addBmpImage2D($GcodeStr, $BmpFilenameStr);
addEndRoutine($GcodeStr);
file_put_contents('bmp.nc', $GcodeStr);
die (str_replace("\n", '<br>',$GcodeStr));

function addBmpImage2D(&$Str, $BmpFilenameStr) {
  $BmpResource = imagecreatefrombmp($BmpFilenameStr);
  $WidthInt = imagesx($BmpResource);
  $HeightInt = imagesy($BmpResource);
  $PixelStepFlt = 1;
  $ColorThresholdFlt = 255.0 / 2.0;
  $ColorThresholdFlt = 255.0 / 2.0;
  $XYScaleFlt = 20.0;
  $DepthFlt = -3.0;
  for($ColInt = 0; $ColInt < $HeightInt; $ColInt += $PixelStepFlt) {
    $BlackBool = false;
    for($RowInt = 0; $RowInt < $WidthInt; $RowInt += $PixelStepFlt) {
      $ZigZagRowInt = ($ColInt % 2 == 0) ? $RowInt : $WidthInt - $RowInt - 1;
      $RGBInt = imagecolorat($BmpResource, $ZigZagRowInt, $ColInt);
      $RInt = ($RGBInt >> 16) & 0xFF;
      $GInt = ($RGBInt >> 8) & 0xFF;
      $BInt = $RGBInt & 0xFF;
      $NormXFlt = 2.0 * (($ZigZagRowInt / $WidthInt) - 0.5);
      $NormYFlt = 2.0 * (($ColInt / $HeightInt) - 0.5);
      $ScaledXFlt = (-1.0 * $XYScaleFlt * $NormXFlt);
      $ScaledYFlt = ($XYScaleFlt * $NormYFlt);
      if (!$BlackBool && // white to black
          ($RInt < $ColorThresholdFlt || 
           $GInt < $ColorThresholdFlt ||
           $BInt < $ColorThresholdFlt)) {
         addXY($Str, false, $ScaledXFlt, $ScaledYFlt);
         addZ($Str, true, $DepthFlt);
         $BlackBool = !$BlackBool;
      } elseif ($BlackBool && // black to white
                ($RInt > $ColorThresholdFlt &&
                 $GInt > $ColorThresholdFlt &&
                 $BInt > $ColorThresholdFlt)) {
        addXY($Str, true, $ScaledXFlt, $ScaledYFlt);
        addZ($Str, false, 0.0);
        $BlackBool = !$BlackBool;
      }
    }
  }
}

function addLine(&$Str, $LineStr) {
 $Str .= $LineStr . lineBreak();
}

function lineBreak() {
  return "\r\n";
}

function addStartRoutine(&$Str) {
  addLine($Str, "G92X0Y0");
  addLine($Str, "G92Z0");
  addLine($Str, "G90");
  addLine($Str, "G1Z0F80"); // 80% speed
  addLine($Str, "M03 S3000"); // 3000 spindle rate
}

function addXY(&$Str, $CutBool, $XFlt, $YFlt) {
  $CmdStr = $CutBool ? "G1" : "G0";
  addLine($Str, $CmdStr . " X" . $XFlt . " Y" . $YFlt);
}

function addZ(&$Str, $CutBool, $ZFlt) {
  $CmdStr = $CutBool ? "G1" : "G0";
  addLine($Str, $CmdStr . "Z" . $ZFlt);
}

function addEndRoutine(&$Str) {
  addZ($Str, false, 0);
  addXY($Str, false, 0, 0);
  addLine($Str, "M05"); // Spindle stop
  addLine($Str, "M02"); // Program end
}

?>