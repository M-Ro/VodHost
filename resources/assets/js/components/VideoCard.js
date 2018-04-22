import React, { Component } from 'react';
import ReactDOM from 'react-dom';

export default class VideoCard extends Component {

    constructor(props) {
        super(props);

        var thumb = "";

        if(props.video.state == 'processing') {
            thumb = '/img/processing.png';
        } else {
            thumb = contentUrl + '/thumb/' + props.video.id + '/thumb_0.jpg'
        }

        this.state = {
            video: props.video,
            contentUrl: contentUrl,
            thumb: thumb,
            anim_i: 1
        }

        this.mouseEnter = this.mouseEnter.bind(this);
        this.mouseLeave = this.mouseLeave.bind(this);
        this.tick = this.tick.bind(this);
    }

    // Animate the thumbnail
    tick() {
        this.state.thumb = (contentUrl + '/thumb/' + this.state.video.id + '/thumb_' + this.state.anim_i + '.jpg')

        this.setState((prevState, props) => {
          return {thumb: contentUrl + '/thumb/' + this.state.video.id + '/thumb_' + this.state.anim_i + '.jpg'};
        });

        this.state.anim_i += 1;
        if(this.state.anim_i > 5) {
            this.state.anim_i = 0;
        }
    }

    componentWillUnmount() {
        clearInterval(this.interval);
    }

    render() {
        var video = this.state.video;

        var url = "/view/" + video.id;

        return (
            <div className="video-card" onMouseEnter={this.mouseEnter} onMouseLeave={this.mouseLeave}>
                <a href={url}><img src={this.state.thumb}></img>
                <h4>{video.title}</h4></a>
                <h5>Uploaded by: <strong>{video.uploader}</strong></h5>
                <h5>{video.views} views &#8226; {video.created_at}</h5>
            </div>
        );
    }

    mouseEnter() {
        if(this.state.video.state != 'processing') {
            this.interval = setInterval(this.tick, 500);
        }
    }

    mouseLeave() {
        clearInterval(this.interval);

        this.setState((prevState, props) => {
          return {
              thumb: contentUrl + '/thumb/' + props.video.id + '/thumb_0.jpg',
              anim_i: 1
            };
        });

    }
}

if (document.getElementById('video-card')) {
    ReactDOM.render(<VideoCard />, document.getElementById('video-card'));
}
