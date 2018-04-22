import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import axios from 'axios';
import VideoCard from './VideoCard';

export default class VideoList extends Component {

    constructor(props) {
        super(props);

        this.state = {
            videos: [],
            contentUrl: contentUrl
        }
    }

    componentDidMount() {
        /* Fetch recent broadcasts from the REST API */
        axios.get('/broadcast/recent')
            .then(response => this.setState({videos: JSON.parse(response.data.replace(/\\/g,''))}))
            .catch(error => console.log(error))
    }

    render() {
        var videoElements = this.state.videos.map(function(video) {
            return <li key={video.id}><VideoCard video={video} /></li>;
        });

        return (
            <div className="video-list">
                <ul>
                    {videoElements}
                </ul>
            </div>
        );
    }
}

if (document.getElementById('video-grid')) {
    ReactDOM.render(<VideoList />, document.getElementById('video-grid'));
}
