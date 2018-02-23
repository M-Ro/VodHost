<?php

namespace VodHost\Backend\Tests;

require 'src/classes/vprocessor.php';

require_once 'vendor/autoload.php';

class VProcessorTest extends \PHPUnit_Framework_TestCase
{
    private $vprocessor;

    private $settings;
 
    protected function setUp()
    {
        $this->vprocessor = new \VodHost\Backend\VProcessor("testdata/test_video.avi");

        $this->settings = [
        	'width' => '320',
        	'height' => '180',
        	'thumbcount' => 6,
        	'target' => 'test_output/',
        	'output_filename' => 'test.mp4'
        ];

        if(!is_dir($this->settings['target']))
        	mkdir($this->settings['target'], 0755);

    }
 
    protected function tearDown()
    {
        $this->vprocessor = NULL;
    }
 
    public function testStaticThumb()
    {
    	$this->vprocessor->generateStaticThumbnail($this->settings);

    	// Validate the frame was generated
    	$result = file_exists($this->settings['target'] . 'thumb_0.jpg');
        $this->assertEquals($result, true);

         // Cleanup
        unlink($this->settings['target'] . 'thumb_0.jpg');
    }

    public function testMultipleThumbnails()
    {
    	$this->vprocessor->generateThumbnailSet($this->settings);

    	// Verify the correct number of frames was generated
    	$err = false;
        for($i = 0; $i < $this->settings['thumbcount']; $i++)
        	if(!file_exists($this->settings['target'] . "thumb_$i.jpg"))
        		$err = true;

        $this->assertEquals($err, false);

        // Cleanup
        foreach (glob($this->settings['target'] . "*.jpg") as $imgfile)
			unlink($imgfile);
    }

    public function testTransmuxing()
    {
    	$this->vprocessor->transmuxToMP4($this->settings);

    	// Verify an mp4 file was created
    	$result = file_exists($this->settings['target'] . $this->settings['output_filename']);
        $this->assertEquals($result, true);

        // Cleanup
        unlink($this->settings['target'] . $this->settings['output_filename']);
    }

    public function testThumbnailScaling()
    {
    	// Generate the frames first
    	$this->vprocessor->generateThumbnailSet($this->settings);

    	// Scale them to size defined in settings
    	$this->vprocessor->scaleThumbnails($this->settings);

    	// Verify each frame exists and is correct size
    	$err = false;
        for($i = 0; $i < $this->settings['thumbcount']; $i++) {
        	$info = getimagesize($this->settings['target'] . "thumb_$i.jpg");
        	if($info[0] != $this->settings['width'] || $info[1] != $this->settings['height'])
        		$err = true;
        }


        $this->assertEquals($err, false);

        // Cleanup
        foreach (glob($this->settings['target'] . "*.jpg") as $imgfile)
			unlink($imgfile);
    }
 
}

?>
