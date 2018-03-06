<?php

namespace VodHost\Backend;

/**
 * Responsible for all videostream processing operations
 *
 * This class is responsible for handling various operations on video files such as:
 * Transmuxing of content into mp4 from other container formats (flv, avi...)
 * Transcoding of other codecs into standardized H264/AAC for web consumption
 * Generation of thumbnails from videos for easy frontend previewing.
 */
class VProcessor
{
    private $filepath;

    private $video;
 
    /**
     * Handles initialization of the class - creates an FFMpeg decoder instance and
     * loads the specified video file.
     *
     * @param  string $path - path of videofile
     * @throws Exception if provided argument is invalid or file cannot be accessed.
     */
    public function __construct(string $path)
    {
        if (!isset($path) || trim($path) === '') {
            throw new \Exception(__METHOD__ . ': $path is null or empty');
        }

        if (!file_exists($path)) {
            throw new \Exception(__METHOD__ . ': cannot access file: ' . $path);
        }

        $this->filepath = $path;

        /* Create instance of the ffmpeg decoder and attempt to load video file */
        $ffmpeg = \FFMpeg\FFMpeg::create();
        try {
            $this->video = $ffmpeg->open($this->filepath);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Exports a single imageframe representing the first frame of the video stream
     * for thumbnailing
     *
     * @param  array $settings - settings array - 'target' required
     * @throws Exception if provided argument is invalid or required fields missing.
     */
    public function generateStaticThumbnail(array $settings)
    {
        if (!isset($settings) || empty($settings)) {
            throw new \Exception(__METHOD__ . ':invalid settings');
        }

        if (!isset($this->video)) {
            throw new \Exception(__METHOD__ . ': FFMpeg\Video not loaded');
        }

        // Generate a thumbnail from the first frame in the video
        $this->video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(0))->save($settings['target'] . 'thumb_0.jpg');
    }

    /**
     * Exports a series of imageframes spread evenly throughout the video for
     * thumbnailing
     *
     * @param  array $settings - settings array - 'target', 'thumbcount' required
     * @throws Exception if provided argument is invalid or required fields missing.
     */
    public function generateThumbnailSet(array $settings)
    {
        if (!isset($settings) || empty($settings)) {
            throw new \Exception(__METHOD__ . ':invalid settings');
        }

        if (!isset($this->video)) {
            throw new \Exception(__METHOD__ . ': FFMpeg\Video not loaded');
        }

        // Get the duration of the first video stream
        try {
            $len = floor($this->video->getStreams()->first()->get('duration'));
        } catch (Exception $e) {
            throw $e;
        }
        
        /* Divide length of stream evenly by number of thumbnails we wish to create */
        if (!isset($settings['thumbcount'])) {
            throw new \Exception(__METHOD__ . ':thumbcount not provided');
        }

        $delta = floor($len / $settings['thumbcount']);

        /* Generate each thumbnail seperated by <delta> seconds in the video */
        $timestamp = 0; // time (in seconds) from video to take thumbnail
        for ($i = 0; $i < $settings['thumbcount']; $i++) {
            $this->video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($timestamp))
                ->save($settings['target'] . "thumb_$i.jpg");
            $timestamp += $delta;
        }
    }

    /**
     * Transfers the video and audio streams in a non-MP4 file into an MP4 container
     *  suitable for web streaming.
     *
     * @param  array $settings - settings array - 'target', 'output_filename' required
     * @throws Exception if provided argument is invalid or required fields missing.
     */
    public function transmuxToMP4(array $settings)
    {
        if (!isset($settings) || empty($settings)) {
            throw new \Exception(__METHOD__ . ':invalid settings');
        }

        if (!is_dir($settings['target'])) {
            throw new \Exception(__METHOD__ . ": $target not a directory");
        }
        if (!isset($settings['output_filename'])) {
            throw new \Exception(__METHOD__ . ': output_filename not provided in settings');
        }

        if (!isset($this->video)) {
            throw new \Exception(__METHOD__ . ': FFMpeg\Video not loaded');
        }

        $format = new \FFMpeg\Format\Video\X264;
        // this is fucking stupid, well done PHP-FFmpeg
        $format->setAdditionalParameters(array('-vcodec', 'copy', '-acodec', 'copy', '-movflags', 'faststart'));

        $this->video->save($format, $settings['target'] . $settings['output_filename']);
    }

    /**
     * @stub
     */
    public function transcode(array $settings)
    {
        throw new \Exception(__METHOD__ . "Not implemented yet");
    }

    /**
     * Scales images to a specified size. Used to scale the full frames
     * ffmpeg exports into thumbnail sized images.
     *
     * @param  array $settings - settings array - 'width', 'height', 'target' required
     * @throws Exception if provided argument is invalid or required fields missing.
     */
    public function scaleThumbnails(array $settings)
    {
        if (!isset($settings) || empty($settings)) {
            throw new \Exception(__METHOD__ . ':invalid settings');
        }

        if (!is_dir($settings['target'])) {
            throw new \Exception(__METHOD__ . ": $target not a directory");
        }

        if (!isset($settings['width']) || !isset($settings['height'])) {
            throw new \Exception(__METHOD__ . ": Thumbnail dimensions not supplied");
        }

        $w = $settings['width'];
        $h = $settings['height'];

        foreach (glob($settings['target'] . "*.jpg") as $imgfile) {
            /* Rescale the frame to thumbnail size */
            $img = imagecreatefromjpeg($imgfile);
            $img_scaled = imagescale($img, $w, $h, IMG_BILINEAR_FIXED);
            imagejpeg($img_scaled, $imgfile, 90);
            imagedestroy($img);
            imagedestroy($img_scaled);
        }
    }
}
